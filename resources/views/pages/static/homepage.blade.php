<x-layouts::site.layout :title="$data->heading ?? $data->title" :site="$site ?? null">
    <div class="mx-auto">
        <section class="bg-white lg:grid lg:place-content-center">
            <div class="mx-auto w-screen max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8 lg:py-32">
                <div class="mx-auto max-w-prose text-center">
                    <h1 class="text-4xl font-bold text-gray-900 sm:text-5xl">{{ $data->heading }}</h1>

                    <div class="">
                        <img src="{{ $data->gambar?->url }}" alt="" />
                    </div>

                    @if ($data->subheading)
                        <p class="mt-4 text-base text-pretty text-gray-700 sm:text-lg/relaxed">{{ $data->subheading }}</p>
                    @endif
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-3xl px-6 py-12 prose">{!! $data->contentss !!}</div>

        <div class="mx-auto max-w-xl px-6 pb-12">
            @foreach ($data->pilih_gambar ?? [] as $image)
                <img src="{{ $image->url }}" alt="" class="w-full rounded-lg" />
            @endforeach
        </div>

        {{-- Demo/preview of the new @collection/@taxonomy/@sites/@global Blade directives --}}
        <div class="mx-auto max-w-3xl space-y-8 px-6 pb-12">
            <div>
                <h2 class="font-bold text-gray-900">@@global — site_settings</h2>
                @global ("site_settings")
                    <pre class="rounded bg-gray-100 p-3 text-xs">{{ json_encode($global, JSON_PRETTY_PRINT) }}</pre>
                @endglobal
            </div>

            <div>
                <h2 class="font-bold text-gray-900">@@sites — configured sites</h2>
                <ul class="list-disc pl-5">
                    @sites ()
                        <li>{{ $site->name }} ({{ $site->handle }}, {{ $site->locale }})</li>
                    @endsites
                </ul>
            </div>

            <div>
                <h2 class="font-bold text-gray-900">@@collection('pages') — entries in the "pages" collection</h2>
                <ul class="list-disc pl-5">
                    @collection ("pages")
                        <li>{{ $data->title ?? $entry->slug }} ({{ $entry->site }})</li>
                    @endcollection
                </ul>
            </div>

            <div>
                <h2 class="font-bold text-gray-900">@@taxonomy('categories') — terms of the "categories" taxonomy</h2>
                <ul class="list-disc pl-5">
                    @taxonomy ("categories")
                        <li>{{ $data->title ?? $term->slug }}</li>
                    @endtaxonomy
                </ul>
            </div>

            {{--
                @collection()/@taxonomy() second-argument options — see
                App\Support\Templating\TemplateTags for the full behavior of
                each. All are optional and combine freely.
            --}}
            <div>
                <h2 class="font-bold text-gray-900">'site' — only this site's entries</h2>
                <ul class="list-disc pl-5">
                    @collection ("pages", ["site" => $site->handle])
                        <li>{{ $data->title ?? $entry->slug }}</li>
                    @endcollection
                </ul>
            </div>

            <div>
                <h2 class="font-bold text-gray-900">'status' — override the default "published" filter</h2>
                <ul class="list-disc pl-5">
                    @collection ("pages", ["status" => "draft"])
                        <li>{{ $data->title ?? $entry->slug }} ({{ $entry->status }})</li>
                    @endcollection
                </ul>
            </div>

            <div>
                <h2 class="font-bold text-gray-900">'limit' — cap how many rows come back</h2>
                <ul class="list-disc pl-5">
                    @collection ("pages", ["limit" => 3])
                        <li>{{ $data->title ?? $entry->slug }}</li>
                    @endcollection
                </ul>
            </div>

            <div>
                <h2 class="font-bold text-gray-900">'sort' — order by a column or a data field, "field:asc"/"field:desc"</h2>
                <ul class="list-disc pl-5">
                    @collection ("pages", ["sort" => "date:desc", "limit" => 5])
                        <li>{{ $data->title ?? $entry->slug }} ({{ $entry->date }})</li>
                    @endcollection
                </ul>
            </div>

            <div>
                <h2 class="font-bold text-gray-900">'where' — equality filter on a column or a data field</h2>
                <ul class="list-disc pl-5">
                    @collection ("pages", ["where" => ["featured" => true]])
                        <li>{{ $data->title ?? $entry->slug }}</li>
                    @endcollection
                    <li class="text-gray-400 italic">(empty above unless a "pages" entry has a "featured" field set to true)</li>
                </ul>
            </div>

            <div>
                <h2 class="font-bold text-gray-900">'random' — shuffle instead of the default order</h2>
                <ul class="list-disc pl-5">
                    @collection ("pages", ["random" => true, "limit" => 3])
                        <li>{{ $data->title ?? $entry->slug }}</li>
                    @endcollection
                </ul>
            </div>

            <div>
                <h2 class="font-bold text-gray-900">'related' — other entries sharing a taxonomy term with $entry</h2>
                <ul class="list-disc pl-5">
                    @collection ("pages", ["related" => $entry, "limit" => 3])
                        <li>{{ $data->title ?? $entry->slug }}</li>
                    @endcollection
                    <li class="text-gray-400 italic">(empty above unless this entry's blueprint has a "taxonomy" field with terms picked)</li>
                </ul>
            </div>
        </div>
    </div>
</x-layouts::site.layout>
