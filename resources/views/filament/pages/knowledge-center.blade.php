<x-filament-panels::page>
    <div class="space-y-8">

        {{-- 1. HERO BANNER & SEARCH BAR --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-900 via-indigo-900 to-blue-800 p-8 md:p-12 text-white shadow-xl">
            <div class="relative z-10 max-w-2xl mx-auto text-center space-y-4">
                <span class="inline-block px-3 py-1 bg-blue-500/30 text-blue-200 rounded-full text-xs font-semibold uppercase tracking-wider border border-blue-400/20">
                    Pusat Informasi & Edukasi
                </span>
                <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">
                    Bagaimana Kami Bisa Membantu Anda?
                </h1>
                <p class="text-sm text-blue-200">
                    Cari pengumuman terbaru, pedoman teknis EKU, regulasi BI, dan edukasi seputar perbankan di sini.
                </p>

                {{-- Search Box --}}
                <div class="relative max-w-lg mx-auto pt-2">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Ketik kata kunci pencarian (contoh: SOP, Setoran, Batas Pengajuan)..."
                        class="w-full pl-11 pr-4 py-3 bg-white text-gray-900 placeholder-gray-400 rounded-xl shadow-lg border-0 focus:ring-2 focus:ring-blue-400 text-sm"
                    />
                    <x-heroicon-o-magnifying-glass class="w-5 h-5 text-gray-400 absolute left-3.5 top-5" />
                </div>
            </div>
        </div>

        {{-- 2. BERITA UTAMA (FEATURED) --}}
        @if($featuredPosts->isNotEmpty())
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <x-heroicon-s-star class="w-4 h-4 text-amber-500" />
                    <h2 class="text-md font-bold text-gray-900 dark:text-white">Berita Utama</h2>
                </div>
                <div class="grid grid-cols-1 {{ $featuredPosts->count() > 1 ? 'md:grid-cols-2' : '' }} {{ $featuredPosts->count() > 2 ? 'lg:grid-cols-3' : '' }} gap-6">
                    @foreach($featuredPosts as $post)
                        <div
                            wire:click="showPost({{ $post->id }})"
                            class="group cursor-pointer relative overflow-hidden rounded-xl shadow-sm hover:shadow-lg transition-all duration-200 min-h-[220px] flex items-end bg-gray-900"
                        >
                            @if($post->gambar_sampul)
                                <img src="{{ asset('storage/' . $post->gambar_sampul) }}"
                                     class="absolute inset-0 w-full h-full object-cover opacity-70 group-hover:scale-105 group-hover:opacity-60 transition-all duration-300">
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>

                            <div class="relative z-10 p-5 space-y-2 w-full">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 bg-amber-500 text-white rounded text-[10px] font-bold uppercase tracking-wide">Utama</span>
                                    <span class="px-2 py-0.5 bg-white/20 backdrop-blur text-white rounded text-[10px] font-medium">{{ $post->kategori }}</span>
                                </div>
                                <h3 class="font-bold text-white text-base leading-snug line-clamp-2">{{ $post->judul }}</h3>
                                <p class="text-[11px] text-white/70">{{ $post->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 3. GRID KATEGORI --}}
        <div>
            <h2 class="text-md font-bold text-gray-900 dark:text-white mb-4">Pilih Berdasarkan Kategori</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($kategoris as $key => $label)
                    @php
                        $count = \App\Models\KnowledgePost::where('is_published', true)->where('kategori', $key)->count();
                        $isSelected = $selectedKategori === $key;
                    @endphp
                    <div
                        wire:click="selectKategori('{{ $key }}')"
                        class="cursor-pointer p-5 rounded-xl border transition-all duration-200 shadow-sm flex items-start justify-between bg-white dark:bg-gray-800 {{ $isSelected ? 'border-blue-600 ring-2 ring-blue-500/20 bg-blue-50/50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-blue-400' }}"
                    >
                        <div class="space-y-2">
                            <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wide">{{ $count }} Artikel</span>
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm">{{ $label }}</h3>
                        </div>
                        <div class="p-2 bg-blue-50 dark:bg-blue-900/40 rounded-lg text-blue-600 dark:text-blue-400">
                            <x-heroicon-o-folder class="w-5 h-5" />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 4. DAFTAR ARTIKEL & NEWS --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-md font-bold text-gray-900 dark:text-white">
                    {{ $selectedKategori ? 'Kategori: ' . $selectedKategori : 'Semua Berita & Informasi' }}
                </h2>
                @if($selectedKategori)
                    <button wire:click="selectKategori(null)" class="text-xs text-blue-600 hover:underline">Tampilkan Semua</button>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($posts as $post)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            @if($post->gambar_sampul)
                                <img src="{{ asset('storage/' . $post->gambar_sampul) }}" class="w-full h-44 object-cover">
                            @else
                                <div class="w-full h-44 bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center text-gray-400">
                                    <x-heroicon-o-document-text class="w-12 h-12" />
                                </div>
                            @endif

                            <div class="p-5 space-y-2">
                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 rounded font-medium text-xs">{{ $post->kategori }}</span>
                                    <span>{{ $post->created_at->diffForHumans() }}</span>
                                </div>

                                <h3 class="font-bold text-gray-900 dark:text-white text-base line-clamp-2">{{ $post->judul }}</h3>

                                <p class="text-xs text-gray-600 dark:text-gray-300 line-clamp-3 leading-relaxed">
                                    {{ $post->ringkasan ?? Str::limit(strip_tags($post->konten), 120) }}
                                </p>
                            </div>
                        </div>

                        <div class="p-5 pt-0">
                            <button
                                wire:click="showPost({{ $post->id }})"
                                class="w-full py-2 bg-gray-50 dark:bg-gray-700/50 hover:bg-blue-600 hover:text-white text-gray-700 dark:text-gray-200 font-medium text-xs rounded-lg transition duration-200 flex items-center justify-center space-x-1"
                            >
                                <span>Baca Selengkapnya</span>
                                <x-heroicon-m-arrow-right class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full p-8 text-center bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-400">
                        Tidak ada informasi atau berita yang ditemukan.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- 5. MODAL BACA ARTIKEL LENGKAP --}}
        @if($activePost)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto p-6 md:p-8 space-y-6 shadow-2xl relative">

                    <button wire:click="closePost" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-white">
                        <x-heroicon-o-x-mark class="w-6 h-6" />
                    </button>

                    <div class="space-y-3">
                        <span class="px-2.5 py-1 bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 rounded text-xs font-semibold">
                            {{ $activePost->kategori }}
                        </span>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">{{ $activePost->judul }}</h1>
                        <p class="text-xs text-gray-400">Diterbitkan pada {{ $activePost->created_at->translatedFormat('d F Y - H:i') }} WITA</p>
                    </div>

                    @if($activePost->gambar_sampul)
                        <img src="{{ asset('storage/' . $activePost->gambar_sampul) }}" class="w-full max-h-72 object-cover rounded-xl">
                    @endif

                    <div class="prose dark:prose-invert max-w-none text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                        {!! $activePost->konten !!}
                    </div>

                    @if($activePost->file_lampiran)
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl flex items-center justify-between border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center space-x-3">
                                <x-heroicon-o-paper-clip class="w-5 h-5 text-blue-600" />
                                <div>
                                    <h4 class="text-xs font-semibold text-gray-900 dark:text-white">File Lampiran</h4>
                                    <p class="text-[10px] text-gray-400">{{ basename($activePost->file_lampiran) }}</p>
                                </div>
                            </div>

                                href="{{ asset('storage/' . $activePost->file_lampiran) }}"
                                target="_blank"
                                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-medium flex items-center space-x-1"
                            >
                                <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                                <span>Unduh File</span>
                            </a>
                        </div>
                    @endif

                    <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button wire:click="closePost" class="px-5 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 text-gray-800 dark:text-white rounded-lg text-xs font-semibold">
                            Tutup
                        </button>
                    </div>

                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
