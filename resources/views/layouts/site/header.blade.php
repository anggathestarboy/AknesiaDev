@props ([
    "site" => null,
])

@navigation ("header", ["site" => $site?->handle])

    <header class="border-b border-zinc-200 bg-white">
        <div class="container mx-auto flex items-center justify-between gap-6 py-3">
            <a href="/" class="flex items-center gap-2">
                    <img src="{{ $navigation->site_logo->url }}" alt="{{ $navigation->site_logo->alt }}" width="{{ $navigation->site_logo->width }}" height="{{ $navigation->site_logo->height }}" class="h-10 w-auto" />
            </a>
            <nav class="flex items-center gap-6">
                @foreach ($navigation->navbar_loop ?? [] as $item)
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
        </div>
    </header>
@endnavigation
