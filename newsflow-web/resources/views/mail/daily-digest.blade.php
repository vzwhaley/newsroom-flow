@component('mail::message')
# Good morning{{ $user->name ? ', '.$user->name : '' }} 👋

@if (! empty($newOnly))
Here’s what’s new since your last digest.
@else
Here are today’s top headlines on your topics.
@endif

@if (! empty($briefing))
## Your Daily Briefing

{{ $briefing }}
@endif

@foreach ($topics as $topic)
## {{ $topic['name'] }}

@foreach ($topic['articles'] as $article)
**[{{ $article->headline }}]({{ $article->url }})**
@if ($article->source){{ $article->source }} — @endif{{ \Illuminate\Support\Str::limit($article->description, 140) }}

@endforeach
@endforeach

@component('mail::button', ['url' => $url])
Open my NewsFlow
@endcomponent

You're receiving this because you turned on the daily digest.
[Unsubscribe with one click]({{ $unsubscribeUrl }}) or manage it from your
[profile]({{ route('profile.edit') }}).

Thanks,<br>
{{ config('app.name') }}
@endcomponent
