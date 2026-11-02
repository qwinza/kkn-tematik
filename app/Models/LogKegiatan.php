<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'tanggal_kehadiran' => 'date:Y-m-d',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'current_step' => 'integer',
        ];
    }

    /**
     * Cek apakah status log masih draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Cek apakah laporan sudah diajukan untuk ditinjau admin.
     */
    public function isWaiting(): bool
    {
        return $this->status === 'menunggu';
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'ditolak'], true);
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

    public function files(): HasMany
    {
        return $this->hasMany(LogKegiatanFile::class);
    }

    public function fotoKegiatanFiles(): HasMany
    {
        return $this->files()->where('jenis_file', 'foto_kegiatan');
    }

    public function fileLuaranFiles(): HasMany
    {
        return $this->files()->where('jenis_file', 'file_luaran');
    }
}
