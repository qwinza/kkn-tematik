<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogKegiatanFile extends Model
{
    protected $fillable = [
        'jenis_file',
        'file_path',
        'nama_file_asli',
        'ukuran_file',
    ];

    protected $appends = ['url'];

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn (): string => route('log-kegiatan.files.show', [
                'log_kegiatan' => $this->log_kegiatan_id,
                'file' => $this->id,
            ]),
        );
    }

    public function logKegiatan(): BelongsTo
    {
        return $this->belongsTo(LogKegiatan::class);
    }
}
