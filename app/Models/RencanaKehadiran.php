<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RencanaKehadiran extends Model
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
            'tanggal_rencana' => 'date',
        ];
    }

    /**
     * Relasi ke peserta pemilik rencana kehadiran.
     *
     * @return BelongsTo<Peserta, $this>
     */
    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    /**
     * Log kegiatan yang memakai sesi kehadiran ini.
     *
     * @return HasMany<LogKegiatan, $this>
     */
    public function logKegiatans(): HasMany
    {
        return $this->hasMany(LogKegiatan::class);
    }
}
