<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peserta extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * Relasi ke akun User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke riwayat log kegiatan peserta.
     */
    public function logKegiatans(): HasMany
    {
        return $this->hasMany(LogKegiatan::class);
    }
}
