<?php

namespace App\Http\Requests;

use App\Models\MasterKegiatan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterKegiatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user?->isAdmin()) {
            return true;
        }

        if (! $user?->isDpp() || ! $user->dppProfile) {
            return false;
        }

        $masterKegiatan = $this->route('master_kegiatan');

        if (
            $masterKegiatan instanceof MasterKegiatan
            && $masterKegiatan->kode_prodi !== $user->dppProfile->kode_prodi
        ) {
            return false;
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if ($user?->isDpp() && $user->dppProfile) {
            $this->merge([
                'kode_prodi' => $user->dppProfile->kode_prodi,
            ]);
        }
    }

    public function rules(): array
    {
        $masterKegiatan = $this->route('master_kegiatan');
        $masterKegiatanId = $masterKegiatan instanceof MasterKegiatan
            ? $masterKegiatan->id
            : $masterKegiatan;

        return [
            'kode_kegiatan' => [
                'required',
                'string',
                'max:50',
                Rule::unique('master_kegiatans', 'kode_kegiatan')
                    ->ignore($masterKegiatanId),
            ],
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'max:100'],
            'kode_prodi' => ['required', 'string', 'exists:prodi,kode_prodi'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_kegiatan.required' => 'Kode kegiatan wajib diisi.',
            'kode_kegiatan.unique' => 'Kode kegiatan sudah digunakan.',
            'nama_kegiatan.required' => 'Nama kegiatan wajib diisi.',
            'kategori.required' => 'Kategori kegiatan wajib diisi.',
            'kode_prodi.required' => 'Prodi kegiatan wajib dipilih.',
            'kode_prodi.exists' => 'Prodi yang dipilih tidak ditemukan.',
        ];
    }
}