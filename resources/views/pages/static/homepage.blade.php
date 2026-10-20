<x-layouts::site.layout :title="$data->heading ?? $data->title" :site="$site ?? null">
    <section x-data="{
        activeSlide: 0,
        ready: false,
        slidesCount: {{ count($data->hero_loop ?? []) }},
        autoplayInterval: null,
        isActive(i) {
            return this.ready && this.activeSlide === i;
        },
        startAutoplay() {
            this.stopAutoplay();
            this.autoplayInterval = setInterval(() => {
                this.activeSlide = (this.activeSlide + 1) % this.slidesCount;
            }, 5000);
        },
        stopAutoplay() {
            clearInterval(this.autoplayInterval);
        }
    }" x-init="setTimeout(() => ready = true, 50);
    if (slidesCount > 1) startAutoplay();" @mouseenter="stopAutoplay()"
        @mouseleave="if (slidesCount > 1) startAutoplay()"
        class="relative w-full h-[calc(100vh-65px)] min-h-[500px] overflow-hidden bg-black text-white">
        @foreach ($data->hero_loop ?? [] as $index => $hero)
            <div class="absolute inset-0 w-full h-full transition-opacity duration-1000 ease-in-out"
                :class="isActive({{ $index }}) ? 'opacity-100' : 'opacity-0'" aria-hidden="true">
                @if (isset($hero->hero_image->url))
                    <img src="{{ $hero->hero_image->url }}" alt="{{ $hero->hero_label ?? 'Hero Image' }}"
                        class="absolute inset-0 w-full h-full object-cover" />
                @endif
                <div class="absolute inset-0 bg-black/40"></div>
            </div>
        @endforeach

        <div class="relative z-10 container mx-auto h-full px-4 lg:px-0 flex items-center">
            <div class="max-w-2xl w-full">

                <div class="grid">
                    @foreach ($data->hero_loop ?? [] as $index => $hero)
                        <div class="col-start-1 row-start-1 self-end lg:self-start space-y-6"
                            :class="isActive({{ $index }}) ? '' : 'pointer-events-none'"
                            :aria-hidden="isActive({{ $index }}) ? 'false' : 'true'">
                            @if (isset($hero->hero_label))
                                <h1 class="text-3xl md:text-5xl font-bold leading-tight text-white transition-all ease-out"
                                    :class="isActive({{ $index }}) ?
                                        'opacity-100 translate-y-0 duration-700 delay-300' :
                                        'opacity-0 -translate-y-8 duration-300 delay-0'">
                                    {{ $hero->hero_label }}
                                </h1>
                            @endif

                            @if (isset($hero->hero_desc))
                                <p class="text-sm md:text-base lg:text-lg text-zinc-200 leading-relaxed transition-all ease-out"
                                    :class="isActive({{ $index }}) ?
                                        'opacity-100 translate-y-0 duration-700 delay-500' :
                                        'opacity-0 -translate-y-8 duration-300 delay-0'">
                                    {{ $hero->hero_desc }}
                                </p>
                            @endif

                            <div class="pt-2 transition-all ease-out"
                                :class="isActive({{ $index }}) ?
                                    'opacity-100 translate-y-0 duration-700 delay-700' :
                                    'opacity-0 -translate-y-8 duration-300 delay-0'">
                                <a href="{{ $data->hero_cta_link ?? '#' }}"
                                    :tabindex="isActive({{ $index }}) ? 0 : -1"
                                    class="inline-flex uppercase items-center justify-center px-8 py-3.5 rounded-[8px] bg-red-500 text-white font-semibold text-sm transition-all duration-300 hover:bg-red-700 hover:shadow-lg">
                                    {{ $data->hero_cta_label }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if (count($data->hero_loop ?? []) > 1)
                    <div class="flex lg:hidden flex-row items-center gap-3 pt-10">
                        @foreach ($data->hero_loop ?? [] as $index => $hero)
                            <button @click="activeSlide = {{ $index }}" type="button"
                                aria-label="Slide {{ $index + 1 }}" class="focus:outline-none">
                                <span class="block h-2 rounded-full bg-white transition-all duration-300"
                                    :class="activeSlide === {{ $index }} ? 'w-8 opacity-100' :
                                        'w-2 opacity-50 hover:opacity-100'"></span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @if (count($data->hero_loop ?? []) > 1)
            <div class="hidden lg:block absolute inset-0 z-20 pointer-events-none">
                <div class="container mx-auto h-full flex items-center justify-end lg:pr-1">
                    <div class="pointer-events-auto flex flex-col items-center gap-3">
                        @foreach ($data->hero_loop ?? [] as $index => $hero)
                            <button @click="activeSlide = {{ $index }}" type="button"
                                aria-label="Slide {{ $index + 1 }}" class="focus:outline-none">
                                <span class="block w-2 rounded-full bg-white transition-all duration-300"
                                    :class="activeSlide === {{ $index }} ? 'h-8 opacity-100' :
                                        'h-2 opacity-50 hover:opacity-100'"></span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </section>

    @php
        $overviewImage = $data->overview_image ?? null;
        $overviewImage = is_array($overviewImage)
            ? \App\Modules\Media\Models\AssetMeta::find(reset($overviewImage) ?: null)
            : $overviewImage;
    @endphp

    <section class="w-full bg-white py-16 md:py-24">
        <div class="container mx-auto px-4 lg:px-0">
            <div class="max-w-3xl mx-auto flex flex-col items-center text-center">

                <div class="mb-8 md:mb-10">
                    <img src="{{ $overviewImage?->url }}" alt="Akenesia Logo"
                        class="h-20 md:h-28 w-auto object-contain" />
                </div>

                <h2
                    class="text-2xl font-bold text-zinc-900 leading-tight md:leading-snug mb-8">
                    {{ $data->overview_heading }}
                </h2>

                <div
                    class="text-sm sm:text-base text-zinc-500 max-w-2xl leading-relaxed [&_a]:text-blue-600 hover:[&_a]:underline [&_a]:transition-colors">
                    {!! $data->overview_desc !!}
                </div>

            </div>
        </div>
    </section>

    <section class="w-full py-16 lg:py-20 bg-white">
        <div class="container mx-auto px-4 lg:px-0">
            <div class="mb-10">
                <span class="block text-sm font-bold uppercase tracking-wide text-red-500 mb-2">
                    {{ $data->news_section_label }}
                </span>
                <h2 class="text-2xl md:text-3xl font-bold text-zinc-900">
                    {{ $data->news_section_heading }}

                </h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-10">
                @collection('news', ['limit' => 3])
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
