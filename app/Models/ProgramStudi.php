<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramStudi extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * Relasi ke peserta mahasiswa pada program studi ini.
     *
     * @return HasMany<Peserta, $this>
     */
    public function pesertas(): HasMany
    {
        return $this->hasMany(Peserta::class);
    }
}
