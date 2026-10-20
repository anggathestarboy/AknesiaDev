<x-layouts::site.layout :title="$data->heading ?? $data->title" :site="$site ?? null">
 <section class="w-full py-16 lg:py-20 bg-white">
        <div class="container mx-auto px-4 lg:px-0">
            <div class="mb-10">
                <span class="block text-sm font-bold uppercase tracking-wide text-red-500 mb-2">
                    {{ $data->overview_heading }}
                </span>
                <div class="text-2xl md:text-3xl font-bold text-zinc-900">
                    {!! $data->overview_desc !!}

                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-10">
                @collection('news')
                    @php
                        $newsCollection = \App\Modules\Collections\Models\Collection::where(
                            'handle',
                            $entry->collection,
                        )->first();
                        $newsUrl = $newsCollection
                            ? app(\App\Modules\Collections\Services\EntryUrlResolver::class)->url(
                                $newsCollection,
                                $entry,
                            )
                            : null;
                    @endphp
                    <a href="{{ $newsUrl ?? '#' }}" class="group">
                        <div>
                            @php
                                $newsImage = $data->news_image;
                                if (is_array($newsImage)) {
                                    $newsImage = \App\Modules\Media\Models\AssetMeta::find(reset($newsImage) ?: null);
                                }

                                $newsDate = $entry->date
                                    ? \Illuminate\Support\Carbon::parse($entry->date)->format('F, jS Y')
                                    : null;
                                $newsAuthor = \App\Models\User::find($entry->seoFields()['meta_author'] ?? '')?->name;
                            @endphp
                            <div class="w-full max-w-[381px] h-[325px] overflow-hidden rounded-xl">
                                <img src="{{ $newsImage?->url }}" alt="Lorem ipsum dolor sit amet consectetur"
                                    class="w-full h-full object-cover transition-transform duration-300 ease-in-out group-hover:scale-105" />
                            </div>
                            <h3 class="mt-5 text-lg font-bold text-zinc-900 max-w-[400px] line-clamp-2 leading-snug">
                                {{ $data->news_title }}
                            </h3>
                            @if ($newsDate || $newsAuthor)
                                <p class="mt-3 text-sm text-zinc-500">
                                    @if ($newsDate)
                                        {{ $newsDate }}
                                    @endif
                                    @if ($newsDate && $newsAuthor)
                                        <span class="mx-1 text-zinc-300">|</span>
                                    @endif
                                    @if ($newsAuthor)
                                        By {{ $newsAuthor }}
                                    @endif
                                </p>
                            @endif
                        </div>
                    </a>
                @endcollection
            </div>
        </div>
    </section>

</x-layouts::site.layout>
