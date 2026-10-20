<x-layouts::site.layout :title="$data->heading ?? $data->title" :site="$site ?? null">

    <section class="relative overflow-hidden bg-linear-to-b from-green-100/60 to-green-50 py-12 lg:py-16">
  <div class="pointer-events-none absolute -left-40 top-1/3 size-150 rounded-full bg-green-200/30 blur-2xl"></div>
  <div class="pointer-events-none absolute -right-32 -top-20 size-130 rounded-full bg-white/40 blur-2xl"></div>

  <div class="relative container mx-auto">
    <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-3 text-sm">
      <a href="#" class="text-slate-600 hover:text-green-600">Beranda</a>
      <svg class="size-4 text-slate-500" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m8 5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <a href="#" class="text-slate-600 hover:text-green-600">Berita</a>
      <svg class="size-4 text-slate-500" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m8 5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <span aria-current="page" class="font-medium text-green-600">{{ $data->title }}</span>
    </nav>

    <div class="mt-12 grid items-center gap-10 lg:mt-16 lg:grid-cols-2 lg:gap-16">
      <div>
        <h1 class="text-4xl font-bold leading-[1.1] tracking-tight text-slate-900 sm:text-5xl">
         {{$data->title}}
        </h1>

        <dl class="mt-8 flex flex-wrap gap-x-8 gap-y-4">
          <div>
            <dt class="text-sm text-slate-500">{{ $site?->name == "Indonesia" ? "Penulis" : "Author" }}</dt>
            <dd class="mt-2 font-medium uppercase text-slate-800">{{ $data->author?->name }}</dd>
          </div>
          <div>
            <dt class="text-sm text-slate-500">{{ $site?->name == "Indonesia" ? "Tanggal Publikasi" : "Date" }}</dt>
            <dd class="mt-2 font-medium uppercase text-slate-800">{{ $data->date }}</dd>
          </div>
          <div>
            <dt class="text-sm text-slate-500">{{ $site?->name == "Indonesia" ? "Kategori" : "Category" }}</dt>
            <dd class="mt-2 font-medium uppercase text-slate-800">General</dd>
          </div>
        </dl>
      </div>
      <figure>
        <img
          src="https://placehold.co/960x610"
          alt="Tim PT Citra Borneo Utama Tbk dengan spanduk audit sertifikasi ISCC EU Point of Origin Waste &amp; Residue"
          class="aspect-16/10 w-full rounded-2xl object-cover shadow-sm"
        />
      </figure>
    </div>
  </div>
</section>

</x-layouts::site.layout>
