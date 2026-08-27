@php
    $deviasiList = $this->getDeviasi();
    $rupiah = fn ($v) => \App\Support\Rupiah::format((float) $v);
    $filterAktif = $this->jenisFilter !== 'Semua';
@endphp

<x-filament-widgets::widget>
    <div class="w-full bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5 space-y-4">

        {{-- HEADER --}}
        <div class="flex items-start justify-between gap-4">

            <div>
                <h3 class="font-semibold text-gray-800 dark:text-white">
                    Deviasi & Akumulasi Realisasi
                </h3>

                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                    Perbandingan antara target (forecast) dengan
                    <strong>total akumulasi</strong> dari semua riwayat file realisasi.
                </p>
            </div>

            {{-- FILTER (mengikuti gaya native filter Filament: icon-button + badge + panel "Filters") --}}
            <div
                class="relative shrink-0"
                x-data="{ open: false }"
            >
                <button
                    type="button"
                    @click="open = !open"
                    @click.outside="open = false"
                    class="fi-icon-btn relative flex items-center justify-center
                           w-9 h-9 rounded-lg
                           text-gray-400 hover:text-gray-500
                           dark:text-gray-500 dark:hover:text-gray-400
                           hover:bg-gray-50 dark:hover:bg-white/5
                           transition duration-75 outline-none"
                    title="Filter jenis"
                >
                    <x-heroicon-m-funnel class="w-5 h-5" />

                    {{-- Badge angka, hanya tampil jika filter aktif (bukan Semua) --}}
                    @if ($filterAktif)
                        <span
                            class="absolute -top-1 -right-1 flex items-center justify-center
                                   w-4 h-4 rounded-full
                                   bg-primary-600 dark:bg-primary-500
                                   text-white text-[10px] font-medium
                                   ring-2 ring-white dark:ring-gray-900"
                        >
                            1
                        </span>
                    @endif
                </button>

                {{-- PANEL FILTER --}}
                <div
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 z-20 mt-2 w-56
                           rounded-xl bg-white dark:bg-gray-900
                           shadow-lg ring-1 ring-gray-950/5 dark:ring-white/10
                           divide-y divide-gray-100 dark:divide-white/10
                           overflow-hidden"
                >

                    {{-- HEADER PANEL --}}
                    <div class="flex items-center justify-between gap-x-4 px-4 py-3">
                        <span class="text-sm font-semibold text-gray-950 dark:text-white">
                            Filters
                        </span>

                        @if ($filterAktif)
                            <button
                                type="button"
                                wire:click="setJenisFilter('Semua')"
                                @click="open = false"
                                class="text-sm font-medium text-danger-600 hover:text-danger-500 dark:text-danger-400 dark:hover:text-danger-300 transition-colors"
                            >
                                Reset filters
                            </button>
                        @endif
                    </div>

                    {{-- OPSI FILTER --}}
                    <div class="p-4 space-y-2">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Jenis
                        </label>

                        @foreach (['Semua', 'Setoran', 'Penarikan'] as $opsi)
                            <button
                                type="button"
                                wire:click="setJenisFilter('{{ $opsi }}')"
                                @click="open = false"
                                class="w-full flex items-center justify-between
                                       px-3 py-2 rounded-lg
                                       text-sm text-left
                                       hover:bg-gray-50 dark:hover:bg-white/5
                                       transition-colors
                                       {{ $this->jenisFilter === $opsi
                                            ? 'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400 font-medium'
                                            : 'text-gray-700 dark:text-gray-300' }}"
                            >
                                <span>{{ $opsi }}</span>

                                @if ($this->jenisFilter === $opsi)
                                    <x-heroicon-m-check class="w-4 h-4" />
                                @endif
                            </button>
                        @endforeach
                    </div>

                </div>
            </div>

        </div>

        @if (count($deviasiList) > 0)
            <div class="w-full overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-800">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/70 text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-4 py-3 font-semibold">Bulan</th>
                            <th class="px-4 py-3 font-semibold">Jenis</th>
                            <th class="px-4 py-3 font-semibold text-right">Target (Forecast)</th>
                            <th class="px-4 py-3 font-semibold text-right">Total UPB</th>
                            <th class="px-4 py-3 font-semibold text-right">Total UPK</th>
                            <th class="px-4 py-3 font-semibold text-right">Total Realisasi</th>
                            <th class="px-4 py-3 font-semibold text-center">Status</th>
                            <th class="px-4 py-3 font-semibold text-right">Deviasi (Sisa / Over)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($deviasiList as $row)
                            @php
                                $isOver = $row['deviasi'] < 0;
                                $isSisa = $row['deviasi'] > 0;

                                if ($isSisa) {
                                    $status = ['label' => 'Sisa Target', 'color' => 'amber'];
                                } elseif ($isOver) {
                                    $status = ['label' => 'Over Realisasi', 'color' => 'rose'];
                                } else {
                                    $status = ['label' => 'Sesuai Target', 'color' => 'emerald'];
                                }
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200 font-medium">
                                    {{ $row['bulan'] }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-medium {{ $row['jenis'] === 'Setoran' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' }}">
                                        {{ $row['jenis'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">
                                    {{ $rupiah($row['forecast']) }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">
                                    {{ $rupiah($row['total_upb'] ?? 0) }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">
                                    {{ $rupiah($row['total_upk'] ?? 0) }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">
                                    {{ $rupiah($row['realisasi']) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="inline-flex flex-col items-center">
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-{{ $status['color'] }}-50 text-{{ $status['color'] }}-700 dark:bg-{{ $status['color'] }}-500/10 dark:text-{{ $status['color'] }}-400">
                                            {{ $status['label'] }}
                                        </span>
                                        <span class="text-[10px] mt-1 text-{{ $status['color'] }}-600 dark:text-{{ $status['color'] }}-400 font-medium">
                                            {{ $row['persen_deviasi'] > 0 ? '+' : '' }}{{ $row['persen_deviasi'] }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-{{ $status['color'] }}-600 dark:text-{{ $status['color'] }}-400">
                                    {{ \App\Support\Rupiah::formatMines($row['deviasi']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="w-full text-center text-gray-400 dark:text-gray-500 text-sm py-8 border border-dashed border-gray-200 dark:border-gray-800 rounded-xl">
                @if ($filterAktif)
                    Tidak ada data {{ strtolower($this->jenisFilter) }} yang bisa dibandingkan.
                @else
                    Belum ada data yang bisa dibandingkan.
                @endif
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
