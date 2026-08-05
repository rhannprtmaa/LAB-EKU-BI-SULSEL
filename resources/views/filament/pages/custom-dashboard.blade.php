<x-filament-panels::page>
    <div class="flex flex-col gap-3" style="height: calc(100vh - 6rem); min-height: 500px;">
        <div class="flex items-center justify-between gap-4 shrink-0">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>

            @if (filament()->isGlobalSearchEnabled())
                <div class="hidden sm:block w-64">
                    @livewire(\Filament\Livewire\GlobalSearch::class)
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 shrink-0">
            @php
                $colorMap = [
                    'green'  => ['bg' => 'bg-green-50 dark:bg-green-900/20', 'text' => 'text-green-700 dark:text-green-400'],
                    'yellow' => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/20', 'text' => 'text-yellow-700 dark:text-yellow-400'],
                    'red'    => ['bg' => 'bg-red-50 dark:bg-red-900/20', 'text' => 'text-red-700 dark:text-red-400'],
                    'blue'   => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/20', 'text' => 'text-indigo-700 dark:text-indigo-400'],
                ];
            @endphp

                @php
                $colorMap = [
                    'green' => [
                        0 => ['bg' => 'bg-green-50 dark:bg-green-950/40', 'text' => 'text-green-400 dark:text-green-600'],
                        1 => ['bg' => 'bg-green-100 dark:bg-green-900/50', 'text' => 'text-green-600 dark:text-green-400'],
                        2 => ['bg' => 'bg-green-200 dark:bg-green-800/60', 'text' => 'text-green-700 dark:text-green-300'],
                        3 => ['bg' => 'bg-green-300 dark:bg-green-700/70', 'text' => 'text-green-800 dark:text-green-200'],
                        4 => ['bg' => 'bg-green-500 dark:bg-green-600', 'text' => 'text-white dark:text-white'],
                    ],
                    'yellow' => [
                        0 => ['bg' => 'bg-yellow-50 dark:bg-yellow-950/40', 'text' => 'text-yellow-500 dark:text-yellow-600'],
                        1 => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/50', 'text' => 'text-yellow-600 dark:text-yellow-400'],
                        2 => ['bg' => 'bg-yellow-200 dark:bg-yellow-800/60', 'text' => 'text-yellow-700 dark:text-yellow-300'],
                        3 => ['bg' => 'bg-yellow-300 dark:bg-yellow-700/70', 'text' => 'text-yellow-800 dark:text-yellow-200'],
                        4 => ['bg' => 'bg-yellow-500 dark:bg-yellow-600', 'text' => 'text-white dark:text-white'],
                    ],
                    'red' => [
                        0 => ['bg' => 'bg-red-50 dark:bg-red-950/40', 'text' => 'text-red-400 dark:text-red-600'],
                        1 => ['bg' => 'bg-red-100 dark:bg-red-900/50', 'text' => 'text-red-600 dark:text-red-400'],
                        2 => ['bg' => 'bg-red-200 dark:bg-red-800/60', 'text' => 'text-red-700 dark:text-red-300'],
                        3 => ['bg' => 'bg-red-300 dark:bg-red-700/70', 'text' => 'text-red-800 dark:text-red-200'],
                        4 => ['bg' => 'bg-red-500 dark:bg-red-600', 'text' => 'text-white dark:text-white'],
                    ],
                    'blue' => [
                        0 => ['bg' => 'bg-indigo-50 dark:bg-indigo-950/40', 'text' => 'text-indigo-400 dark:text-indigo-600'],
                        1 => ['bg' => 'bg-indigo-100 dark:bg-indigo-900/50', 'text' => 'text-indigo-600 dark:text-indigo-400'],
                        2 => ['bg' => 'bg-indigo-200 dark:bg-indigo-800/60', 'text' => 'text-indigo-700 dark:text-indigo-300'],
                        3 => ['bg' => 'bg-indigo-300 dark:bg-indigo-700/70', 'text' => 'text-indigo-800 dark:text-indigo-200'],
                        4 => ['bg' => 'bg-indigo-500 dark:bg-indigo-600', 'text' => 'text-white dark:text-white'],
                    ],
                ];
            @endphp

@foreach ($this->getStats() as $stat)

    @php
        $tier = $stat['tier'] ?? 0;
        $color = $stat['color'] ?? 'blue';
        $warna = $colorMap[$color][$tier] ?? $colorMap['blue'][0];
    @endphp

    <div class="rounded-2xl p-4 {{ $warna['bg'] }} border border-black/5 shadow-sm hover:shadow-md transition-all duration-300">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium {{ $tier >= 4 ? 'text-white/80' : 'text-gray-600 dark:text-gray-300' }}">
                {{ $stat['label'] }}
            </span>

            <x-dynamic-component
                :component="$stat['icon']"
                class="w-5 h-5 {{ $warna['text'] }}"
            />
        </div>

        <div class="text-2xl font-bold {{ $warna['text'] }}">
            {{ number_format($stat['value']) }}
        </div>
    </div>

@endforeach
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-4 shrink-0">
            {{ $this->form }}
        </div>
        <div class="flex-1 min-h-0 flex flex-col bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-4">
            <div class="flex items-center justify-between mb-3 shrink-0">
                <h3 class="font-semibold text-gray-800 dark:text-white">Grafik</h3>

               @if ($this->data['jenisGrafik'] === 'forecast_eku')
                    <div class="flex items-center gap-4 text-sm">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Setoran</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span> Penarikan</span>
                    </div>
                @elseif ($this->data['jenisGrafik'] === 'realisasi_eku')
                    <div class="flex items-center gap-4 text-sm">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Realisasi Setoran</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span> Realisasi Penarikan</span>
                    </div>
                @elseif ($this->data['jenisGrafik'] === 'deviasi_forecast')
                    <div class="flex items-center gap-4 text-sm">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background-color:#f59e0b"></span> Under-Realisasi</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background-color:#3b82f6"></span> Over-Realisasi</span>
                    </div>
                @endif
            </div>

            <div class="flex-1 min-h-0 flex items-center justify-center">
                @if ($this->data['jenisGrafik'] === 'tukab')

                    <div class="flex flex-col items-center justify-center text-center text-gray-400">
                        <x-heroicon-o-chart-bar class="w-10 h-10 mb-3" />
                        <p class="font-medium">Grafik ini belum tersedia</p>
                        <p class="text-sm max-w-sm mt-1">
                            Fitur input data untuk grafik ini masih dalam tahap pengembangan pada fase berikutnya.
                        </p>
                    </div>
                @elseif (in_array($this->data['jenisGrafik'], ['realisasi_eku', 'deviasi_forecast']))
                    @php($pie = $this->data['jenisGrafik'] === 'realisasi_eku' ? $this->realisasiPieData() : $this->deviasiPieData())

                    @if (! $pie['hasData'])
                        <div class="flex flex-col items-center justify-center text-center text-gray-400">
                            <x-heroicon-o-inbox class="w-10 h-10 mb-3" />
                            <p class="font-medium">
                                @if ($this->data['jenisGrafik'] === 'realisasi_eku')
                                    Belum ada data <span class="font-semibold text-gray-500">Realisasi</span> yang diinput pada periode ini
                                @else
                                    Belum ada <span class="font-semibold text-gray-500">Deviasi</span> yang bisa dihitung pada periode ini
                                @endif
                            </p>
                            <p class="text-sm max-w-sm mt-1">
                                @if ($this->data['jenisGrafik'] === 'realisasi_eku')
                                    Grafik ini menampilkan data realisasi terbaru yang diinput User BI untuk transaksi berstatus Disetujui.
                                @else
                                    Grafik ini membandingkan Forecast dengan Realisasi terbaru pada transaksi berstatus Disetujui.
                                @endif
                            </p>
                        </div>
                    @else
                        <div class="relative w-full h-full flex flex-col sm:flex-row items-center justify-center gap-6"
                             x-data="{ tip: null, mx: 0, my: 0 }" @mousemove="mx = $event.clientX; my = $event.clientY">

                            <div class="relative shrink-0" style="width: min(320px, 100%); aspect-ratio: 1 / 1;">
                                <svg viewBox="0 0 300 300" class="w-full h-full">
                                    @foreach ($pie['slices'] as $i => $slice)
                                        <path d="{{ $slice['d'] }}" fill="{{ $slice['color'] }}"
                                              class="transition-opacity duration-150"
                                              :class="tip && tip.i !== {{ $i }} ? 'opacity-60' : 'opacity-100'"
                                              style="cursor: pointer;"
                                              @mouseenter="mx = $event.clientX; my = $event.clientY; tip = {
                                                  i: {{ $i }},
                                                  label: @js($slice['label']),
                                                  value: @js($slice['valueFmt']),
                                                  persen: @js($slice['persen']),
                                              }"
                                              @mousemove="mx = $event.clientX; my = $event.clientY"
                                              @mouseleave="tip = null" />
                                    @endforeach

                                    <text x="{{ $pie['cx'] }}" y="{{ $pie['cy'] - 6 }}" text-anchor="middle" font-size="13"
                                          class="fill-gray-500 dark:fill-gray-400 font-medium">Total</text>
                                    <text x="{{ $pie['cx'] }}" y="{{ $pie['cy'] + 16 }}" text-anchor="middle" font-size="16"
                                          class="fill-gray-800 dark:fill-white font-bold">{{ $pie['totalFmt'] }}</text>
                                </svg>
                            </div>

                            <div class="flex flex-col gap-2 text-sm">
                                @foreach ($pie['slices'] as $slice)
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $slice['color'] }}"></span>
                                        <span class="text-gray-600 dark:text-gray-300">{{ $slice['label'] }}</span>
                                        <span class="font-semibold text-gray-800 dark:text-white ml-1">{{ $slice['persen'] }}%</span>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Box overview info saat hover --}}
                            <div x-show="tip" x-cloak
                                 class="pointer-events-none fixed z-50 rounded-lg bg-blue-900 dark:bg-gray-800 border border-white/10 text-white text-xs px-3.5 py-2.5 shadow-xl min-w-[170px]"
                                 :style="`left: ${mx + 16}px; top: ${my + 16}px;`">
                                <template x-if="tip">
                                    <div class="space-y-1">
                                        <p class="font-semibold text-sm text-white" x-text="tip.label"></p>
                                        <p class="flex items-center justify-between gap-3">
                                            <span class="text-white/80">Nominal</span>
                                            <span class="font-medium" x-text="tip.value"></span>
                                        </p>
                                        <p class="flex items-center justify-between gap-3">
                                            <span class="text-white/80">Persentase</span>
                                            <span class="font-medium" x-text="tip.persen + '%'"></span>
                                        </p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    @endif
                @else
                    @php($chart = $this->chartSvgData())

                    @if (! $chart['hasData'])
                        <div class="flex flex-col items-center justify-center text-center text-gray-400">
                            <x-heroicon-o-inbox class="w-10 h-10 mb-3" />
                            <p class="font-medium">Belum ada pengajuan yang <span class="font-semibold text-gray-500">Disetujui</span> pada periode ini</p>
                            <p class="text-sm max-w-sm mt-1">Grafik Forecast EKU hanya menampilkan data yang sudah divalidasi &amp; disetujui User BI.</p>
                        </div>
                    @else
                        <div class="relative w-full h-full" x-data="{ tip: null, mx: 0, my: 0 }" @mousemove="mx = $event.clientX; my = $event.clientY">
                            <svg viewBox="0 0 {{ $chart['width'] }} {{ $chart['height'] }}" class="w-full h-full" preserveAspectRatio="none">
                                {{-- Grid horizontal + label sumbu Y --}}
                                @foreach ($chart['gridLines'] as $line)
                                    <line x1="{{ $chart['paddingLeft'] }}" y1="{{ $line['y'] }}"
                                          x2="{{ $chart['width'] - 20 }}" y2="{{ $line['y'] }}"
                                          stroke="currentColor" class="text-gray-100 dark:text-gray-700" stroke-width="1" />
                                    <text x="0" y="{{ $line['y'] + 4 }}" font-size="11" class="fill-gray-600 dark:fill-gray-300 font-medium">{{ $line['value'] }}</text>
                                @endforeach

                                {{-- Label bulan sumbu X --}}
                                @foreach ($chart['labels'] as $lbl)
                                    <text x="{{ $lbl['x'] }}" y="{{ $chart['height'] - 8 }}" font-size="11"
                                          text-anchor="middle" class="fill-gray-600 dark:fill-gray-300 font-medium">{{ $lbl['label'] }}</text>
                                @endforeach

                                {{-- Garis Penarikan (merah), melengkung (smooth curve) --}}
                                <path d="{{ $chart['penarikanPath'] }}" fill="none" stroke="#fb7185" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />

                                {{-- Garis Setoran (hijau), melengkung (smooth curve) --}}
                                <path d="{{ $chart['setoranPath'] }}" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />

                                {{-- Titik data kecil di tiap bulan (selalu tampak) --}}
                                @foreach ($chart['points'] as $p)
                                    <circle cx="{{ $p['x'] }}" cy="{{ $p['ySetoran'] }}" r="3" fill="#10b981" />
                                    <circle cx="{{ $p['x'] }}" cy="{{ $p['yPenarikan'] }}" r="3" fill="#fb7185" />
                                @endforeach

                                {{-- Garis panduan vertikal + titik yang membesar saat di-hover --}}
                                @foreach ($chart['points'] as $i => $p)
                                    <line x1="{{ $p['x'] }}" y1="{{ $chart['paddingTop'] }}"
                                          x2="{{ $p['x'] }}" y2="{{ $chart['paddingTop'] + $chart['plotHeight'] }}"
                                          stroke="currentColor" class="text-gray-300 dark:text-gray-600" stroke-width="1"
                                          stroke-dasharray="4 3" x-show="tip && tip.i === {{ $i }}" x-cloak style="display:none" />
                                    <circle cx="{{ $p['x'] }}" cy="{{ $p['ySetoran'] }}" r="6" fill="#10b981" stroke-width="1.5"
                                            class="stroke-gray-900 dark:stroke-white"
                                            x-show="tip && tip.i === {{ $i }}" x-cloak style="display:none" />
                                    <circle cx="{{ $p['x'] }}" cy="{{ $p['yPenarikan'] }}" r="6" fill="#fb7185" stroke-width="1.5"
                                            class="stroke-gray-900 dark:stroke-white"
                                            x-show="tip && tip.i === {{ $i }}" x-cloak style="display:none" />
                                @endforeach

                                {{-- Area transparan per bulan untuk mendeteksi hover mendekati garis --}}
                                @foreach ($chart['points'] as $i => $p)
                                    <rect x="{{ $p['x'] - ($chart['stepX'] / 2) }}" y="{{ $chart['paddingTop'] }}"
                                          width="{{ $chart['stepX'] > 0 ? $chart['stepX'] : $chart['width'] }}" height="{{ $chart['plotHeight'] }}"
                                          fill="transparent" style="cursor: pointer;"
                                          @mouseenter="mx = $event.clientX; my = $event.clientY; tip = {
                                              i: {{ $i }},
                                              bulan: @js($p['bulan']),
                                              setoran: @js($p['setoranFmt']),
                                              penarikan: @js($p['penarikanFmt']),
                                              total: @js($p['totalFmt']),
                                              persenSetoran: @js($p['persenSetoran']),
                                              persenPenarikan: @js($p['persenPenarikan']),
                                          }"
                                          @mousemove="mx = $event.clientX; my = $event.clientY"
                                          @mouseleave="tip = null" />
                                @endforeach
                            </svg>

                            {{-- Box overview info saat hover --}}
                            <div x-show="tip" x-cloak
                              class="pointer-events-none fixed z-50 rounded-lg bg-blue-900 dark:bg-gray-800 border border-white/10 text-white text-xs px-3.5 py-2.5 shadow-xl min-w-[190px]"
                                 :style="`left: ${mx + 16}px; top: ${my + 16}px;`">
                                <template x-if="tip">
                                    <div class="space-y-1.5">
                                        <p class="font-semibold text-sm text-white" x-text="tip.bulan"></p>
                                        <p class="flex items-center justify-between gap-3">
                                            <span class="flex items-center gap-1.5 text-white/80">
                                                <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span> Setoran (UPB)
                                            </span>
                                            <span class="font-medium" x-text="tip.setoran + ' (' + tip.persenSetoran + '%)'"></span>
                                        </p>
                                        <p class="flex items-center justify-between gap-3">
                                            <span class="flex items-center gap-1.5 text-white/80">
                                                <span class="w-2 h-2 rounded-full bg-rose-400 inline-block"></span> Penarikan (UPK)
                                            </span>
                                            <span class="font-medium" x-text="tip.penarikan + ' (' + tip.persenPenarikan + '%)'"></span>
                                        </p>
                                        <p class="flex items-center justify-between gap-3 pt-1.5 border-t border-white/10">
                                            <span class="text-white/80">Total Nominal</span>
                                            <span class="font-semibold" x-text="tip.total"></span>
                                        </p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>