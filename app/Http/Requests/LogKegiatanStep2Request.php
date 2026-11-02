<?php

namespace App\Http\Requests;

use App\Models\LogKegiatan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LogKegiatanStep2Request extends FormRequest
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
        /** @var LogKegiatan|null $logKegiatan */
        $logKegiatan = $this->route('log_kegiatan');
        $hasExistingFoto = $logKegiatan?->fotoKegiatanFiles()->exists() ?? false;
        $isMultiple = is_array($this->file('foto_kegiatan'));
        $fileRules = ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:51200'];

        return [
            'deskripsi_kegiatan' => ['required', 'string', 'min:10'],
            'foto_kegiatan' => [
                $hasExistingFoto ? 'nullable' : 'required',
                ...($isMultiple ? ['array'] : $fileRules),
            ],
            'foto_kegiatan.*' => $isMultiple ? $fileRules : [],
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
            'deskripsi_kegiatan.required' => 'Deskripsi kegiatan wajib diisi.',
            'deskripsi_kegiatan.min' => 'Deskripsi kegiatan minimal 10 karakter.',
            'foto_kegiatan.required' => 'Foto dokumentasi kegiatan wajib diunggah.',
            'foto_kegiatan.image' => 'Berkas harus berupa gambar.',
            'foto_kegiatan.mimes' => 'Format foto harus berupa JPG, JPEG, PNG, atau WEBP.',
            'foto_kegiatan.max' => 'Ukuran foto maksimal adalah 50MB.',
        ];
    }
}
