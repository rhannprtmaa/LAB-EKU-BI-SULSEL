<?php

namespace App\Models;

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
