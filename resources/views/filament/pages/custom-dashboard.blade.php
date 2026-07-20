<x-filament-panels::page>

    {{-- Wrapper flex-col dibikin pas 1 layar (tidak scroll). Angka "6rem" di
         bawah ini adalah perkiraan tinggi padding bawaan Filament di luar
         wrapper ini — kalau di layarmu masih kepotong sedikit / masih ada
         sisa gap, tinggal naik/turunkan angka itu saja. --}}
    <div class="flex flex-col gap-3" style="height: calc(100vh - 6rem); min-height: 500px;">

        {{-- ============================== --}}
        {{-- HEADER CUSTOM (pengganti topbar) --}}
        {{-- Cukup judul + search saja di sini — account & notifikasi sudah
             otomatis ada di bagian bawah sidebar (fi-sidebar-footer), jadi
             tidak perlu diduplikasi di sini. --}}
        {{-- ============================== --}}
        <div class="flex items-center justify-between gap-4 shrink-0">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>

            @if (filament()->isGlobalSearchEnabled())
                <div class="hidden sm:block w-64">
                    @livewire(\Filament\Livewire\GlobalSearch::class)
                </div>
            @endif
        </div>

        {{-- ============================== --}}
        {{-- 4 KARTU RINGKASAN --}}
        {{-- ============================== --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 shrink-0">
            @php
                $colorMap = [
                    'green'  => ['bg' => 'bg-green-50 dark:bg-green-900/20', 'text' => 'text-green-700 dark:text-green-400'],
                    'yellow' => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/20', 'text' => 'text-yellow-700 dark:text-yellow-400'],
                    'red'    => ['bg' => 'bg-red-50 dark:bg-red-900/20', 'text' => 'text-red-700 dark:text-red-400'],
                    'blue'   => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/20', 'text' => 'text-indigo-700 dark:text-indigo-400'],
                ];
            @endphp

            @foreach ($this->getStats() as $stat)
                <div class="rounded-2xl p-4 {{ $colorMap[$stat['color']]['bg'] }} border border-black/5 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $stat['label'] }}</span>
                        <x-dynamic-component :component="$stat['icon']" class="w-5 h-5 {{ $colorMap[$stat['color']]['text'] }}" />
                    </div>
                    <div class="text-2xl font-bold {{ $colorMap[$stat['color']]['text'] }}">
                        {{ number_format($stat['value']) }}
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ============================== --}}
        {{-- FILTER: Jenis Grafik / Periode / Jenis Bank --}}
        {{-- ============================== --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-4 shrink-0">
            {{ $this->form }}
        </div>

        {{-- ============================== --}}
        {{-- KARTU GRAFIK — mengisi semua sisa ruang yang ada (flex-1) --}}
        {{-- ============================== --}}
        <div class="flex-1 min-h-0 flex flex-col bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-4">
            <div class="flex items-center justify-between mb-3 shrink-0">
                <h3 class="font-semibold text-gray-800 dark:text-white">Grafik</h3>

                @if ($this->data['jenisGrafik'] === 'forecast_eku')
                    <div class="flex items-center gap-4 text-sm">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Setoran</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span> Penarikan</span>
                    </div>
                @endif
            </div>

            <div class="flex-1 min-h-0 flex items-center justify-center">
                @if ($this->data['jenisGrafik'] !== 'forecast_eku')
                   
                    <div class="flex flex-col items-center justify-center text-center text-gray-400">
                        <x-heroicon-o-chart-bar class="w-10 h-10 mb-3" />
                        <p class="font-medium">Grafik ini belum tersedia</p>
                        <p class="text-sm max-w-sm mt-1">
                            Fitur input data untuk grafik ini masih dalam tahap pengembangan pada fase berikutnya.
                        </p>
                    </div>
                @else
                    @php($chart = $this->chartSvgData())

                    @if (! $chart['hasData'])
                        <div class="flex flex-col items-center justify-center text-center text-gray-400">
                            <x-heroicon-o-inbox class="w-10 h-10 mb-3" />
                            <p class="font-medium">Belum ada pengajuan yang <span class="font-semibold text-gray-500">Disetujui</span> pada periode ini</p>
                            <p class="text-sm max-w-sm mt-1">Grafik Forecast EKU hanya menampilkan data yang sudah divalidasi &amp; disetujui User BI.</p>
                        </div>
                    @else
                        <svg viewBox="0 0 {{ $chart['width'] }} {{ $chart['height'] }}" class="w-full h-full" preserveAspectRatio="none">
                            {{-- Grid horizontal + label sumbu Y --}}
                            @foreach ($chart['gridLines'] as $line)
                                <line x1="{{ $chart['paddingLeft'] }}" y1="{{ $line['y'] }}"
                                      x2="{{ $chart['width'] - 20 }}" y2="{{ $line['y'] }}"
                                      stroke="currentColor" class="text-gray-100 dark:text-gray-700" stroke-width="1" />
                                <text x="0" y="{{ $line['y'] + 4 }}" font-size="11" class="fill-gray-400">{{ $line['value'] }}</text>
                            @endforeach

                            {{-- Label bulan sumbu X --}}
                            @foreach ($chart['labels'] as $lbl)
                                <text x="{{ $lbl['x'] }}" y="{{ $chart['height'] - 8 }}" font-size="11"
                                      text-anchor="middle" class="fill-gray-400">{{ $lbl['label'] }}</text>
                            @endforeach

                            {{-- Garis Penarikan (merah) --}}
                            <polyline points="{{ $chart['penarikanPoints'] }}" fill="none" stroke="#fb7185" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />

                            {{-- Garis Setoran (hijau) --}}
                            <polyline points="{{ $chart['setoranPoints'] }}" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
