<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterKegiatanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $masterKegiatanId = $this->route('master_kegiatan')?->id ?? $this->route('master_kegiatan');

        return [
            'kode_kegiatan' => [
                'required',
                'string',
                'max:50',
                Rule::unique('master_kegiatans', 'kode_kegiatan')->ignore($masterKegiatanId),
            ],
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'max:100'],
            'kode_prodi' => ['required', 'string', 'exists:prodi,kode_prodi'],
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
            'kode_kegiatan.required' => 'Kode kegiatan wajib diisi.',
            'kode_kegiatan.unique' => 'Kode kegiatan sudah digunakan.',
            'nama_kegiatan.required' => 'Nama kegiatan wajib diisi.',
            'kategori.required' => 'Kategori kegiatan wajib diisi.',
            'kode_prodi.required' => 'Prodi kegiatan wajib dipilih.',
            'kode_prodi.exists' => 'Prodi yang dipilih tidak ditemukan.',
        ];
    }
}
