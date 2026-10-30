<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterKegiatan extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * Relasi ke log kegiatan yang menggunakan master kegiatan ini.
     */
    public function logKegiatans(): HasMany
    {
        return $this->hasMany(LogKegiatan::class);
    }
}
