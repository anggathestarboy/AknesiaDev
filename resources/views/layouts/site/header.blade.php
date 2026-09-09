@props ([
    "site" => null,
])

<header class="sticky top-0 bg-red-50 z-99">
    <div class="container mx-auto py-4">
        <a href="{{ $site?->url ?: '/' }}" class="text-lg font-semibold text-zinc-900"> {{ $site?->name ?? config("app.name") }} </a>
        {{ $slot ?? "" }}
    </div>
</header>
