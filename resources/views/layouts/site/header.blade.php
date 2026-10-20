@props ([
    "site" => null,
])

@navigation ("header", ["site" => $site?->handle])
    <header class="border-b border-zinc-200 bg-white">
        <div class="container mx-auto grid grid-cols-[1fr_auto_1fr] items-center gap-6 py-3">
            <a href="/" class="flex items-center gap-2 justify-self-start">
                    <span>Aksnesia</span>
            </a>
            <nav class="flex items-center gap-6">
                @foreach ($data->navbar_loop ?? [] as $item)
                    @if ($item->menu_level && count($item->child_loop ?? []))
                        <div class="group relative">
                            <a href="{{ $item->navbar_url ?: '#' }}" class="inline-flex items-center gap-1 text-sm font-medium text-zinc-700 hover:text-zinc-900">
                                {{ $item->navbar_label }}
                                <span aria-hidden="true">&#9662;</span>
                            </a>
                            <div class="invisible absolute left-0 top-full z-10 min-w-48 rounded-md border border-zinc-200 bg-white py-1 opacity-0 shadow-lg transition group-focus-within:visible group-focus-within:opacity-100 group-hover:visible group-hover:opacity-100">
                                @foreach ($item->child_loop as $child)
                                    <a href="{{ $child->child_menu_url ?: '#' }}" class="block px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-100">{{ $child->child_menu_label }}</a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ $item->navbar_url ?: '#' }}" class="text-sm font-medium text-zinc-700 hover:text-zinc-900">{{ $item->navbar_label }}</a>
                    @endif
                @endforeach
            </nav>
            @php($activeHandle = $site?->handle)
            <div class="group relative justify-self-end text-sm font-medium">
                <button type="button" class="inline-flex items-center gap-1 text-zinc-700 hover:text-zinc-900">
                    {{ strtoupper($site?->lang ?? app()->getLocale()) }}
                    <span aria-hidden="true">&#9662;</span>
                </button>
                <div class="invisible absolute right-0 top-full z-10 min-w-32 rounded-md border border-zinc-200 bg-white py-1 opacity-0 shadow-lg transition group-focus-within:visible group-focus-within:opacity-100 group-hover:visible group-hover:opacity-100">
                    @sites ()
                        <a href="{{ $site->url }}" @class(['block px-4 py-2 text-zinc-700 hover:bg-zinc-100', 'font-semibold bg-zinc-50' => $site->handle === $activeHandle])>{{ $site->name }} ({{ strtoupper($site->lang) }})</a>
                    @endsites
                </div>
            </div>
        </div>
    </header>
@endnavigation
