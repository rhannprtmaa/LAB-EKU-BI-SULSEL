{{-- Class "eku-details-wrapper" dipakai theme.css untuk menghilangkan border/
     radius/shadow BAGIAN BAWAH tabel bawaan Filament, supaya panel ringkasan
     di bawahnya bisa nyambung jadi SATU bentuk kotak yang sama (bukan 2 kotak
     terpisah yang cuma ditempelkan). --}}
<div class="fi-resource-relation-manager eku-details-wrapper">
    {{ $this->content }}

    @php($ringkasan = $this->getRingkasan())

    <div class="bg-white dark:bg-gray-900 border border-t-0 border-gray-100 dark:border-gray-800 rounded-b-2xl shadow-sm px-5 py-4">
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

    <x-filament-panels::unsaved-action-changes-alert />
</div>
