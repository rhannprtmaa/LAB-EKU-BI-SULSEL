<x-filament-panels::page>
    <div class="space-y-6">

        {{-- TOMBOL KEMBALI --}}
        <div>
            <a href="{{ \App\Filament\Pages\ManagementEku::getUrl() }}" class="inline-flex items-center space-x-2 text-sm font-medium text-gray-500 hover:text-[#054177] dark:text-gray-400 dark:hover:text-gray-200 transition">
                <x-heroicon-o-arrow-left class="w-4 h-4" />
                <span>Kembali ke Management EKU</span>
            </a>
        </div>

       <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- TOTAL BATASAN SETORAN --}}
    <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-white/10">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                Total Batasan Setoran
            </p>

            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold
                bg-green-50 text-green-600 border border-green-200
                dark:bg-green-900/30 dark:text-green-400 dark:border-green-800">
                Setoran
            </span>
        </div>

        <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">
            Rp {{ number_format($bank->batasan_setoran ?? 0, 0, ',', '.') }}
        </p>
    </div>


    {{-- TOTAL BATASAN PENARIKAN --}}
    <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-white/10">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                Total Batasan Penarikan
            </p>

            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold
                bg-blue-50 text-blue-600 border border-blue-200
                dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800">
                Penarikan
            </span>
        </div>

        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">
            Rp {{ number_format($bank->batasan_penarikan ?? 0, 0, ',', '.') }}
        </p>
    </div>

</div>

        @php
            $pecahanKeys = ['kertas_100k', 'kertas_50k', 'kertas_20k', 'kertas_10k', 'kertas_5k', 'kertas_2k', 'kertas_1k', 'logam_1k', 'logam_500', 'logam_200', 'logam_100'];
            $pecahanLabels = ['Rp 100.000', 'Rp 50.000', 'Rp 20.000', 'Rp 10.000', 'Rp 5.000', 'Rp 2.000', 'Rp 1.000 (K)', 'Rp 1.000 (L)', 'Rp 500', 'Rp 200', 'Rp 100'];
        @endphp

        {{-- TABEL TUNGGAL DENGAN FITUR SEARCH & FILTER ALPINE.JS --}}
        {{-- State Alpine: menyimpan status filter, input search, dan apakah pop-up filter sedang terbuka --}}
        <div x-data="{ filter: 'All', search: '', showFilter: false }" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-white/10 overflow-hidden mt-6">

            {{-- HEADER TABEL & ACTIONS --}}
            <div class="p-4 border-b border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Rincian Proyeksi EKU Bulanan</h3>

                <div class="flex items-center gap-3">
                    {{-- Kotak Search --}}
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <x-heroicon-m-magnifying-glass class="w-4 h-4 text-gray-400" />
                        </div>
                        <input
                            type="text"
                            x-model="search"
                            placeholder="Search"
                            class="block w-full pl-9 pr-3 py-1.5 text-sm text-gray-900 border border-gray-300 rounded-lg bg-white focus:ring-[#054177] focus:border-[#054177] dark:bg-gray-900 dark:border-white/10 dark:placeholder-gray-400 dark:text-white"
                        >
                    </div>

                    {{-- Tombol Filter Icon --}}
                    <div class="relative">
                        <button
                            @click="showFilter = !showFilter"
                            @click.away="showFilter = false"
                            type="button"
                            class="relative flex items-center justify-center w-9 h-9 text-gray-400 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300 rounded-full hover:bg-gray-50 dark:hover:bg-white/5 transition focus:outline-none"
                        >
                            <x-heroicon-m-funnel class="w-5 h-5" />

                            {{-- Badge Angka 1 jika Filter Aktif (bukan All) --}}
                            <span
                                x-show="filter !== 'All'"
                                x-cloak
                                class="absolute top-0 right-0 flex items-center justify-center w-3.5 h-3.5 text-[9px] font-bold text-white bg-[#054177] rounded-full ring-2 ring-white dark:ring-gray-900"
                            >
                                1
                            </span>
                        </button>

                        {{-- Pop-up Dropdown Filter --}}
                        <div
                            x-show="showFilter"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 z-20 w-72 mt-2 origin-top-right bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-white/5 rounded-xl shadow-lg ring-1 ring-gray-900/5 dark:ring-white/10"
                            style="display: none;"
                        >
                            <div class="px-4 py-3 flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">Filters</span>
                                {{-- Tombol Reset hanya muncul jika filter tidak 'All' --}}
                                <button
                                    @click="filter = 'All'"
                                    x-show="filter !== 'All'"
                                    type="button"
                                    class="text-sm font-medium text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300 transition"
                                >
                                    Reset
                                </button>
                            </div>
                            <div class="p-4 space-y-4">
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Jenis</label>
                                    <select
                                        x-model="filter"
                                        class="block w-full py-2 px-3 text-sm text-gray-900 border border-gray-300 rounded-lg bg-white shadow-sm focus:ring-[#054177] focus:border-[#054177] dark:bg-gray-900 dark:border-white/10 dark:text-white"
                                    >
                                        <option value="All">All</option>
                                        <option value="Setoran">Setoran</option>
                                        <option value="Penarikan">Penarikan</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- BODY TABEL --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                    <thead class="text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-white/10 whitespace-nowrap">
                        <tr>
                            <th class="px-5 py-4 font-semibold">Bulan</th>
                            <th class="px-5 py-4 font-semibold">Jenis</th>
                            @foreach($pecahanLabels as $label)
                                <th class="px-5 py-4 font-semibold text-right">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse($rincian as $row)
                            {{--
                                Logika Filter Alpine.js:
                                Tampilkan baris jika Filter adalah "All" ATAU sesuai dengan jenis transaksi
                                DAN inputan Search cocok dengan teks bulan atau jenis
                            --}}
                            <tr
                                x-show="(filter === 'All' || filter === '{{ $row['jenis'] }}') && ('{{ strtolower($row['bulan']) }}'.includes(search.toLowerCase()) || '{{ strtolower($row['jenis']) }}'.includes(search.toLowerCase()))"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                            >
                                <td class="px-5 py-4 font-medium text-gray-900 dark:text-white">{{ $row['bulan'] ?? '-' }}</td>
                                <td class="px-5 py-4">
                                    @if($row['jenis'] === 'Setoran')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold border bg-green-50 text-green-600 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800">
                                        Setoran
                                    </span>
                                @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold border bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800">
                                            Penarikan
                                        </span>
                                    @endif
                                </td>
                                @foreach($pecahanKeys as $pecahan)
                                    <td class="px-5 py-4 text-right">{{ number_format($row[$pecahan] ?? 0, 0, ',', '.') }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="px-5 py-8 text-center text-gray-400">Data tidak tersedia. Harap upload File Excel Batasan terlebih dahulu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- RINGKASAN TOTAL (style disamakan dengan Rincian Proyeksi EKU Bulanan) --}}
            @php($ringkasan = $this->getRingkasan())

            <div class="border-t border-gray-200 dark:border-white/10 px-5 py-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Ringkasan Total</p>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs text-gray-500">Total Setoran</p>
                        <p class="text-lg font-bold" style="color:#054177;">Rp {{ number_format($ringkasan['totalSetoran'], 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Total Penarikan</p>
                        <p class="text-lg font-bold" style="color:#054177;">Rp {{ number_format($ringkasan['totalPenarikan'], 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Grand Total (Setoran + Penarikan)</p>
                        <p class="text-lg font-bold text-emerald-600">Rp {{ number_format($ringkasan['grandTotal'], 0, ',', '.') }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500">Total UK (Uang Kertas)</p>
                        <p class="text-lg font-bold text-gray-700 dark:text-gray-200">Rp {{ number_format($ringkasan['totalUK'], 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Total UL (Uang Logam)</p>
                        <p class="text-lg font-bold text-gray-700 dark:text-gray-200">Rp {{ number_format($ringkasan['totalUL'], 0, ',', '.') }}</p>
                    </div>
                    <div></div>

                    <div>
                        <p class="text-xs text-gray-500">Total UPB <span class="text-gray-400">(100rb + 50rb)</span></p>
                        <p class="text-lg font-bold text-amber-600">Rp {{ number_format($ringkasan['totalUPB'], 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Total UPK <span class="text-gray-400">(20rb ke bawah + logam)</span></p>
                        <p class="text-lg font-bold text-amber-600">Rp {{ number_format($ringkasan['totalUPK'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
