<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6 mb-4">
        <h3 class="font-semibold text-gray-800 dark:text-white mb-1">Template Saat Ini</h3>

        @php($current = $this->currentTemplate())

        @if ($current)
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <x-heroicon-o-document-text class="w-5 h-5" />
                    {{ $current->nama_file }}
                    <span class="text-gray-400">— diupload {{ $current->created_at->diffForHumans() }}</span>
                </div>

                <a href="{{ Storage::disk('public')->url($current->file_path) }}" target="_blank"
                   class="text-sm font-medium" style="color:#054177;">
                    Unduh →
                </a>
            </div>
        @else
            <p class="text-sm text-gray-400">Belum ada template yang diupload.</p>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 dark:text-white mb-4">Upload Template Baru</h3>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <button type="submit"
                class="rounded-lg px-5 py-2.5 font-semibold text-white transition hover:opacity-90"
                style="background-color:#054177;">
                Simpan Template
            </button>
        </form>
    </div>
</x-filament-panels::page>
