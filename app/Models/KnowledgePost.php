<?php

namespace App\Models;

use App\Services\NotifikasiService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgePost extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(function (KnowledgePost $post) {
            if ($post->is_published) {
                NotifikasiService::pengumuman($post);
            }
        });

        static::updated(function (KnowledgePost $post) {
            // Kirim notifikasi kalau postingan BARU SAJA dipublikasikan
            // (sebelumnya draft, sekarang published) -- supaya tidak
            // berulang setiap kali admin mengedit postingan yang sudah lama.
            if ($post->wasChanged('is_published') && $post->is_published) {
                NotifikasiService::pengumuman($post);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function kategoriOptions(): array
    {
        return [
            'Berita & Pengumuman' => 'Berita & Pengumuman',
            'Panduan & SOP EKU' => 'Panduan & SOP EKU',
            'Regulasi & Kebijakan' => 'Regulasi & Kebijakan',
            'Edukasi Perbankan' => 'Edukasi Perbankan',
        ];
    }
}
