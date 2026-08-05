@php
    $deviasiList = $this->getDeviasi();
    $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
@endphp

<div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5 space-y-4">
    <div>
        <h3 class="font-semibold text-gray-800 dark:text-white">Deviasi per Bulan</h3>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
            Perbandingan antara proyeksi (forecast) yang disetujui dengan realisasi terbaru yang diinput BI.
        </p>
    </div>

    @if (count($deviasiList) > 0)
        <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-800">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/70 text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-2.5 font-semibold">Bulan</th>
                        <th class="px-4 py-2.5 font-semibold">Jenis</th>
                        <th class="px-4 py-2.5 font-semibold text-right">Forecast</th>
                        <th class="px-4 py-2.5 font-semibold text-right">Realisasi</th>
                        <th class="px-4 py-2.5 font-semibold text-right">Deviasi</th>
                        <th class="px-4 py-2.5 font-semibold text-right">% Deviasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($deviasiList as $row)
                        @php
                            $status = $row['deviasi'] > 0
                                ? ['label' => 'Kurang dari Target', 'class' => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400']
                                : ($row['deviasi'] < 0
                                    ? ['label' => 'Melebihi Target', 'class' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400']
                                    : ['label' => 'Sesuai Target', 'class' => 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400']);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                            <td class="px-4 py-2.5 text-gray-700 dark:text-gray-200">{{ $row['bulan'] }}</td>
                            <td class="px-4 py-2.5">
                                <span class="px-2 py-0.5 rounded text-xs font-medium {{ $row['jenis'] === 'Setoran' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400' }}">
                                    {{ $row['jenis'] }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right text-gray-700 dark:text-gray-200">{{ $rupiah($row['forecast']) }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-700 dark:text-gray-200">{{ $rupiah($row['realisasi']) }}</td>
                            <td class="px-4 py-2.5 text-right font-medium text-gray-800 dark:text-white">
                                {{ $row['deviasi'] > 0 ? '+' : '' }}{{ $rupiah($row['deviasi']) }}
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $status['class'] }}">
                                    {{ $row['persen_deviasi'] > 0 ? '+' : '' }}{{ $row['persen_deviasi'] }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center gap-4 text-xs text-gray-400 dark:text-gray-500 pt-1">
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Sesuai target</span>
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Realisasi kurang dari target</span>
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Realisasi melebihi target</span>
        </div>
    @else
        <div class="text-center text-gray-400 dark:text-gray-500 text-sm py-8 border border-dashed border-gray-200 dark:border-gray-800 rounded-xl">
            Belum ada realisasi yang bisa dibandingkan. Input realisasi lewat tabel "Riwayat Input Realisasi" di bawah.
        </div>
    @endif
</div>