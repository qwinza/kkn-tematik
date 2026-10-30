<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogKegiatan extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_kehadiran' => 'date',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    /**
     * Relasi ke peserta pemilik log kegiatan.
     */
    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    /**
     * Relasi ke jenis/master kegiatan yang dilakukan.
     */
    public function masterKegiatan(): BelongsTo
    {
        return $this->belongsTo(MasterKegiatan::class);
    }
}
