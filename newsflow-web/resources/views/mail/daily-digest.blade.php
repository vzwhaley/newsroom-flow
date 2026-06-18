@component('mail::message')
# Good morning{{ $user->name ? ', '.$user->name : '' }} 👋

@if (! empty($newOnly))
Here’s what’s new since your last digest.
@else
Here are today’s top headlines on your topics.
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

You're receiving this because you turned on the daily digest. You can turn it
off any time from your [profile]({{ route('profile.edit') }}).

Thanks,<br>
{{ config('app.name') }}
@endcomponent
