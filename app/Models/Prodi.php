<?php

namespace App\Models;

use Database\Factories\ProdiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prodi extends Model
{
    /** @use HasFactory<ProdiFactory> */
    use HasFactory;

    protected $table = 'prodi';

    protected $primaryKey = 'kode_prodi';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['created_at', 'updated_at'];

    public function pesertas(): HasMany
    {
        return $this->hasMany(Peserta::class, 'kode_prodi', 'kode_prodi');
    }

    public function masterKegiatans(): HasMany
    {
        return $this->hasMany(MasterKegiatan::class, 'kode_prodi', 'kode_prodi');
    }
    public function dppProfiles(): HasMany
    {
        return $this->hasMany(
            DppProfile::class,
            'kode_prodi',
            'kode_prodi'
        );
    }
}
