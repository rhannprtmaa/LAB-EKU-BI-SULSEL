<div class="fi-resource-relation-manager eku-details-wrapper">
    {{ $this->content }}

    @php($ringkasan = $this->getRingkasan())

    <div class="bg-white dark:bg-gray-900 border border-t-0 border-gray-100 dark:border-gray-800 rounded-b-2xl shadow-sm px-5 py-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Ringkasan Total</p>

        {{-- Setoran dan Penarikan adalah 2 hal yang berbeda -- semua breakdown
             (Total, UK, UL, UPB, UPK) dipisah per jenis, tidak digabung. --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            {{-- SETORAN --}}
            <div class="rounded-xl border border-amber-100 dark:border-amber-900/40 bg-amber-50/50 dark:bg-amber-900/10 p-4">
                <p class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wide mb-3">Setoran</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-gray-500">Total</p>
                        <p class="text-base font-bold" style="color:#054177;">{{ \App\Support\Rupiah::format($ringkasan['totalSetoran']) }}</p>
                    </div>
                    <div></div>
                    <div>
                        <p class="text-xs text-gray-500">UK (Uang Kertas)</p>
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ \App\Support\Rupiah::format($ringkasan['totalUKSetoran']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">UL (Uang Logam)</p>
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ \App\Support\Rupiah::format($ringkasan['totalULSetoran']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">UPB <span class="text-gray-400">(100rb+50rb)</span></p>
                        <p class="text-sm font-bold text-amber-600">{{ \App\Support\Rupiah::format($ringkasan['totalUPBSetoran']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">UPK <span class="text-gray-400">(≤20rb+logam)</span></p>
                        <p class="text-sm font-bold text-amber-600">{{ \App\Support\Rupiah::format($ringkasan['totalUPKSetoran']) }}</p>
                    </div>
                </div>
            </div>

            {{-- PENARIKAN --}}
            <div class="rounded-xl border border-blue-100 dark:border-blue-900/40 bg-blue-50/50 dark:bg-blue-900/10 p-4">
                <p class="text-xs font-semibold text-blue-700 dark:text-blue-400 uppercase tracking-wide mb-3">Penarikan</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-gray-500">Total</p>
                        <p class="text-base font-bold" style="color:#054177;">{{ \App\Support\Rupiah::format($ringkasan['totalPenarikan']) }}</p>
                    </div>
                    <div></div>
                    <div>
                        <p class="text-xs text-gray-500">UK (Uang Kertas)</p>
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ \App\Support\Rupiah::format($ringkasan['totalUKPenarikan']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">UL (Uang Logam)</p>
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ \App\Support\Rupiah::format($ringkasan['totalULPenarikan']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">UPB <span class="text-gray-400">(100rb+50rb)</span></p>
                        <p class="text-sm font-bold text-amber-600">{{ \App\Support\Rupiah::format($ringkasan['totalUPBPenarikan']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">UPK <span class="text-gray-400">(≤20rb+logam)</span></p>
                        <p class="text-sm font-bold text-amber-600">{{ \App\Support\Rupiah::format($ringkasan['totalUPKPenarikan']) }}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <x-filament-panels::unsaved-action-changes-alert />
</div>
