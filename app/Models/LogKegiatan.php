<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogKegiatan extends Model
{
    use HasEncryptedRouteKey;
    use HasFactory;

    protected $appends = ['route_key'];

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
     * Durasi kegiatan dalam jam, dihitung dari jam_mulai & jam_selesai
     * dengan fallback ke pola "HH:MM - HH:MM" pada jam_kehadiran.
     */
    public function durasiJam(): float
    {
        $start = $this->toMinutes($this->jam_mulai);
        $end = $this->toMinutes($this->jam_selesai);

        if (
            ($start === null || $end === null)
            && is_string($this->jam_kehadiran)
            && preg_match('/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', $this->jam_kehadiran, $matches)
        ) {
            $start ??= $this->toMinutes($matches[1]);
            $end ??= $this->toMinutes($matches[2]);
        }

        if ($start === null || $end === null || $end <= $start) {
            return 0.0;
        }

        return ($end - $start) / 60;
    }

    private function toMinutes(?string $time): ?int
    {
        if (! is_string($time) || ! preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            return null;
        }

        return ((int) $matches[1]) * 60 + (int) $matches[2];
    }

    /**
     * Relasi ke peserta pemilik log kegiatan.
     */
    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    public function rencanaKehadiran(): BelongsTo
    {
        return $this->belongsTo(RencanaKehadiran::class);
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
