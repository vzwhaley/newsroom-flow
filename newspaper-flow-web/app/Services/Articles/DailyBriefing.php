<?php

namespace App\Services\Articles;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The Pro "front page" briefing: one short Claude-written paragraph that ties
 * together the day's top stories across ALL of the user's topics. Cached on
 * the user for their local day, so it costs at most one LLM call per user/day.
 *
 * When no ANTHROPIC_API_KEY is configured (local dev, pre-launch) it degrades
 * to a deterministic non-AI digest built from the top headline of each topic,
 * flagged with ai=false so clients can label it accordingly.
 */
class DailyBriefing
{
    /**
     * Return today's briefing for the user, generating (and caching) it if
     * needed. Null only when the user has no articles at all.
     *
     * @return array{briefing:string, ai:bool, date:string, cached:bool}|null
     */
    public function for(User $user): ?array
    {
        $tz = $user->timezone ?: config('app.timezone');
        $today = Carbon::now($tz)->toDateString();

        if ($user->briefing && $user->briefing_for?->toDateString() === $today) {
            return [
                'briefing' => $user->briefing,
                'ai'       => $this->llmEnabled(),
                'date'     => $today,
                'cached'   => true,
            ];
        }

        $digest = $this->collectHeadlines($user);

        if (empty($digest)) {
            return null;
        }

        $text = $this->llmEnabled() ? $this->askClaude($user, $digest) : null;
        $ai = $text !== null;
        $text ??= $this->fallback($digest);

        $user->forceFill(['briefing' => $text, 'briefing_for' => $today])->save();

        return [
            'briefing' => $text,
            'ai'       => $ai,
            'date'     => $today,
            'cached'   => false,
        ];
    }

    public function llmEnabled(): bool
    {
        return (bool) config('newspaperflow.llm.enabled') && (bool) config('newspaperflow.llm.api_key');
    }

    /**
     * Top stories per topic (max 4 each, ~24 total) as prompt-ready lines.
     *
     * @return array<int, array{topic:string, articles:array<int, string>}>
     */
    private function collectHeadlines(User $user): array
    {
        $digest = [];
        $total = 0;

        $topics = $user->topics()->with(['articles' => fn ($q) => $q->orderBy('position')->limit(4)])->get();

        foreach ($topics as $topic) {
            if ($total >= 24 || $topic->articles->isEmpty()) {
                continue;
            }

            $lines = [];
            foreach ($topic->articles as $a) {
                $lines[] = $a->headline.($a->description ? ' — '.$a->description : '');
                $total++;
            }

            $digest[] = ['topic' => $topic->name, 'articles' => $lines];
        }

        return $digest;
    }

    private function askClaude(User $user, array $digest): ?string
    {
        $sections = collect($digest)
            ->map(fn ($d) => strtoupper($d['topic'])."\n".collect($d['articles'])->map(fn ($l) => '- '.$l)->implode("\n"))
            ->implode("\n\n");

        $prompt = "You are the editor of {$user->name}'s personal newspaper. Below are today's top "
            ."stories grouped by the topics they follow. Write their morning front-page briefing: "
            ."ONE tight paragraph (4-6 sentences, under 140 words) that connects the most important "
            ."threads across topics. Neutral, factual, energetic. Refer to topics naturally. No "
            ."preamble, no bullet points, no headline — just the paragraph.\n\n".$sections;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => config('newspaperflow.llm.api_key'),
                'anthropic-version' => config('newspaperflow.llm.version', '2023-06-01'),
                'content-type'      => 'application/json',
            ])->timeout(30)->post(config('newspaperflow.llm.endpoint'), [
                'model'      => config('newspaperflow.llm.model'),
                'max_tokens' => 400,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if (! $response->ok()) {
                Log::warning('Claude briefing call failed', ['status' => $response->status()]);

                return null;
            }

            $text = trim((string) $response->json('content.0.text', ''));

            return $text !== '' ? $text : null;
        } catch (\Throwable $e) {
            Log::warning('Claude briefing exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Deterministic non-AI digest: the lead story of each topic, stitched
     * into a readable paragraph. Used when the LLM isn't configured (and as
     * the safety net if a call fails).
     */
    private function fallback(array $digest): string
    {
        $parts = [];

        foreach (array_slice($digest, 0, 5) as $d) {
            $lead = explode(' — ', $d['articles'][0])[0];
            $parts[] = "in {$d['topic']}, \"{$lead}\"";
        }

        $joined = implode('; ', $parts);

        return 'Today across your newsroom: '.$joined.'. Open each topic below for the full picture.';
    }
}
