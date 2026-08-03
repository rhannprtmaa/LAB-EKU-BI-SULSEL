<x-filament-panels::page>
    <div x-data="{ activeTab: 'deadline' }" class="space-y-6">

        {{-- NAVIGASI TAB MENU --}}
        <div class="flex border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl p-2 shadow-sm">
            <button
                type="button"
                @click="activeTab = 'deadline'"
                :class="{ 'bg-[#054177] text-white shadow-sm': activeTab === 'deadline', 'text-gray-600 hover:text-gray-900 dark:text-gray-300': activeTab !== 'deadline' }"
                class="flex items-center space-x-2 px-5 py-2.5 rounded-lg font-medium text-sm transition duration-200"
            >
                <x-heroicon-o-clock class="w-5 h-5" />
                <span>Batas Pengajuan EKU</span>
            </button>

            <button
                type="button"
                @click="activeTab = 'template'"
                :class="{ 'bg-[#054177] text-white shadow-sm': activeTab === 'template', 'text-gray-600 hover:text-gray-900 dark:text-gray-300': activeTab !== 'template' }"
                class="flex items-center space-x-2 px-5 py-2.5 rounded-lg font-medium text-sm transition duration-200"
            >
                <x-heroicon-o-document-arrow-down class="w-5 h-5" />
                <span>Template Kerja EKU</span>
            </button>
        </div>
        <div x-show="activeTab === 'deadline'" class="space-y-6">
            {{-- Form Input Deadline --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Batas Pengajuan EKU</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Tanggal terakhir bank boleh mengajukan/mengubah data EKU.</p>
                </div>

                <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tanggal Batas Pengajuan</label>
                        <input type="datetime-local" wire:model="tanggal_deadline" class="w-full text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Keterangan (Opsional)</label>
                        <input type="text" wire:model="keterangan_deadline" placeholder="Contoh: Batas akhir pengajuan" class="w-full text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                    </div>

                    <div>
                        <button type="submit" class="w-full bg-[#054177] hover:bg-[#04345f] text-white font-medium py-2.5 px-4 rounded-lg transition duration-200 shadow-sm flex items-center justify-center space-x-2 text-sm">
                            <x-heroicon-m-check-circle class="w-4 h-4" />
                            <span>Simpan</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Tabel Riwayat Deadline --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-md font-bold text-gray-900 dark:text-white mb-3">Daftar Batas Waktu Pengajuan</h3>
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                        <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3">No</th>
                                <th class="px-4 py-3">Batas Waktu Pengajuan</th>
                                <th class="px-4 py-3">Keterangan</th>
                                <th class="px-4 py-3">Dibuat Pada</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse(\App\Models\EkuDeadline::latest('id')->get() as $index => $item)
                                @php
                                    $rawDate = $item->batas_waktu ?? $item->deadline_at ?? $item->tanggal_deadline;
                                    $formattedDate = '-';

                                    if ($rawDate) {
                                        try {
                                            $formattedDate = \Carbon\Carbon::parse($rawDate)->translatedFormat('d F Y - H:i') . ' WITA';
                                        } catch (\Exception $e) {
                                            $formattedDate = (string) $rawDate;
                                        }
                                    }
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <td class="px-4 py-3 font-medium">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $formattedDate }}
                                    </td>
                                    <td class="px-4 py-3">{{ $item->keterangan ?? '-' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->diffForHumans() : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            wire:click="hapusDeadline({{ $item->id }})"
                                            wire:confirm="Apakah Anda yakin ingin menghapus batas waktu ini?"
                                            class="text-red-600 hover:text-red-800 text-xs font-semibold"
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-center text-gray-400">Belum ada data batas waktu pengajuan EKU.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'template'" class="space-y-6">

            {{-- FORM UPLOAD TEMPLATE EXCEL --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Upload Master Template Kerja EKU</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Unggah file master Excel Setoran atau Penarikan baru agar dapat diunduh oleh perbankan.</p>
                </div>

                <form wire:submit.prevent="save" class="space-y-4">
                    {{ $this->form }}

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 px-6 rounded-lg transition duration-200 shadow-sm flex items-center space-x-2 text-sm">
                            <x-heroicon-m-arrow-up-tray class="w-4 h-4" />
                            <span>Unggah Template</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- DAFTAR MASTER TEMPLATE YANG AKTIF --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-md font-bold text-gray-900 dark:text-white mb-3">Daftar Master Template EKU Saat Ini</h3>
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                        <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3">Jenis Template</th>
                                <th class="px-4 py-3">Nama File</th>
                                <th class="px-4 py-3">Terakhir Diperbarui</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse(\App\Models\EkuTemplate::all() as $tpl)
                                @php
                                    $filePath = $tpl->file_path ?? $tpl->path ?? $tpl->file;
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $tpl->jenis ?? $tpl->nama ?? 'Template EKU' }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-blue-600 dark:text-blue-400">
                                        {{ $filePath ? basename($filePath) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ $tpl->updated_at ? $tpl->updated_at->diffForHumans() : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center space-x-2">
                                        @if($filePath)
                                            <a
                                                href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($filePath) }}"
                                                target="_blank"
                                                class="inline-flex items-center space-x-1 px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-md hover:bg-emerald-100 text-xs font-medium"
                                            >
                                                <x-heroicon-m-arrow-down-tray class="w-3.5 h-3.5" />
                                                <span>Download</span>
                                            </a>
                                        @endif

                                        <button
                                            type="button"
                                            wire:click="hapusTemplate({{ $tpl->id }})"
                                            wire:confirm="Apakah Anda yakin ingin menghapus template ini?"
                                            class="inline-flex items-center px-2.5 py-1.5 bg-red-50 text-red-600 border border-red-200 rounded-md hover:bg-red-100 text-xs font-medium"
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-4 text-center text-gray-400">Belum ada master template EKU yang diunggah.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</x-filament-panels::page>
