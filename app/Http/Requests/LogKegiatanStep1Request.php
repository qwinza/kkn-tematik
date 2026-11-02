<?php

namespace App\Http\Requests;

use App\Models\LogKegiatan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LogKegiatanStep1Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->isMahasiswa() || ! $user->peserta) {
            return false;
        }

        /** @var LogKegiatan|null $logKegiatan */
        $logKegiatan = $this->route('log_kegiatan');
        if ($logKegiatan instanceof LogKegiatan) {
            return $logKegiatan->peserta_id === $user->peserta->id;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'master_kegiatan_id' => [
                'required',
                Rule::exists('master_kegiatans', 'id')
                    ->where(fn ($query) => $query->where('kode_prodi', $this->user()?->peserta?->kode_prodi ?? '')),
            ],
            'tanggal_kehadiran' => ['required', 'date'],
            'jam_kehadiran' => [
                'required',
                Rule::in([
                    '07:00 - 10:00 (Pagi)',
                    '10:00 - 13:00 (Siang)',
                    '13:00 - 16:00 (Sore)',
                ]),
            ],
            'lokasi_kegiatan' => [
                'required',
                Rule::in(['Lapangan', 'Kampus ITENAS']),
            ],
            'tipe_lokasi' => ['nullable', 'in:otomatis,manual'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * Custom message validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'master_kegiatan_id.required' => 'Pilih jenis master kegiatan.',
            'master_kegiatan_id.exists' => 'Master kegiatan tidak ditemukan.',
            'tanggal_kehadiran.required' => 'Tanggal kehadiran wajib diisi.',
            'tanggal_kehadiran.date' => 'Format tanggal kehadiran tidak valid.',
            'jam_kehadiran.required' => 'Pilih sesi jam kehadiran.',
            'lokasi_kegiatan.required' => 'Pilih lokasi kegiatan.',
            'latitude.numeric' => 'Nilai latitude harus berupa angka koordinat valid.',
            'longitude.numeric' => 'Nilai longitude harus berupa angka koordinat valid.',
        ];
    }
}
