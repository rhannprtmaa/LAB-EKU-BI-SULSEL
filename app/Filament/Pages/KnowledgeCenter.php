<?php

namespace App\Filament\Pages;

use App\Models\KnowledgePost;
use BackedEnum;
use Filament\Pages\Page;

class KnowledgeCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $title = 'Knowledge Center EKU';
    protected static ?string $navigationLabel = 'Knowledge Center';
    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.knowledge-center';

    public string $search = '';
    public ?string $selectedKategori = null;
    public ?KnowledgePost $activePost = null;

    public function selectKategori(?string $kategori): void
    {
        $this->selectedKategori = $this->selectedKategori === $kategori ? null : $kategori;
    }

    public function showPost(int|string $id): void
    {
        $this->activePost = KnowledgePost::find($id);
    }

    public function closePost(): void
    {
        $this->activePost = null;
    }

    public function getViewData(): array
    {
        $query = KnowledgePost::where('is_published', true);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('judul', 'like', '%' . $this->search . '%')
                  ->orWhere('konten', 'like', '%' . $this->search . '%')
                  ->orWhere('ringkasan', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->selectedKategori) {
            $query->where('kategori', $this->selectedKategori);
        }

        $featuredPosts = KnowledgePost::where('is_published', true)->where('is_featured', true)->latest()->take(3)->get();
        $posts = $query->latest()->get();

        return [
            'featuredPosts' => $featuredPosts,
            'posts' => $posts,
            'kategoris' => KnowledgePost::kategoriOptions(),
        ];
    }
}
