<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DppAccessRequest extends Model
{
    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    protected $fillable = [
        'username_sikad',
        'nomor_dosen',
        'nama',
        'kode_fakultas_sikad',
        'kode_prodi_sikad',
        'nidn',
        'gelar_depan',
        'gelar_belakang',
        'status_sikad',
        'approval_status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->approval_status === self::STATUS_MENUNGGU;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::STATUS_DISETUJUI;
    }

    public function isRejected(): bool
    {
        return $this->approval_status === self::STATUS_DITOLAK;
    }
}