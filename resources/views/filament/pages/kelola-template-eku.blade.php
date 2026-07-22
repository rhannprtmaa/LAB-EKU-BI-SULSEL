<x-filament-panels::page>
    <div class="rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden max-w-xl mb-6"
         style="background-color:#EAF1F8;">
        <div class="px-4 py-2.5 border-b border-black/5">
            <p class="text-xs font-semibold uppercase tracking-wide" style="color:#054177;">Template Aktif Saat Ini</p>
        </div>

        <div class="divide-y divide-black/5">
            @foreach ([
                ['label' => 'Setoran', 'icon' => 'heroicon-o-arrow-down-circle', 'template' => $this->templateSetoran()],
                ['label' => 'Penarikan', 'icon' => 'heroicon-o-arrow-up-circle', 'template' => $this->templatePenarikan()],
            ] as $item)
                <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <x-dynamic-component :component="$item['icon']" class="w-4 h-4 shrink-0" style="color:#054177;" />

                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-700 truncate">{{ $item['label'] }}</p>
                            <p class="text-xs text-gray-400 truncate">
                                {{ $item['template']?->nama_file ?? 'Belum ada template' }}
                            </p>
                        </div>
                    </div>

                    @if ($item['template'])
                        <a href="{{ Storage::disk('public')->url($item['template']->file_path) }}" target="_blank"
                           class="inline-flex items-center gap-1 text-xs font-semibold shrink-0" style="color:#054177;">
                            <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
                            Unduh
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6 max-w-xl">
        <div class="flex items-center gap-2 mb-5">
            <x-heroicon-o-arrow-up-tray class="w-5 h-5" style="color:#054177;" />
            <h3 class="font-semibold text-gray-800 dark:text-white">Upload / Ganti Template</h3>
        </div>

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
