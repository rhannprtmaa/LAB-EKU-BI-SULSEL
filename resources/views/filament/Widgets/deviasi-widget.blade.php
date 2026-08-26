@php
    $deviasiList = $this->getDeviasi();
    $rupiah = fn ($v) => \App\Support\Rupiah::format((float) $v);
@endphp

<x-filament-widgets::widget>
    <div class="w-full bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5 space-y-4">
        <div>
            <h3 class="font-semibold text-gray-800 dark:text-white">Deviasi & Akumulasi Realisasi</h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                Perbandingan antara target (forecast) dengan <strong>total akumulasi</strong> dari semua riwayat file realisasi.
            </p>
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
                                    {{ $isOver ? '(Mines) ' : '' }}{{ $rupiah(abs($row['deviasi'])) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="w-full text-center text-gray-400 dark:text-gray-500 text-sm py-8 border border-dashed border-gray-200 dark:border-gray-800 rounded-xl">
                Belum ada data yang bisa dibandingkan.
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
