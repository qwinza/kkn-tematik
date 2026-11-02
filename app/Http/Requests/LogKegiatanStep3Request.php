<?php

namespace App\Http\Requests;

use App\Models\LogKegiatan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LogKegiatanStep3Request extends FormRequest
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
        $hasExistingLuaran = $logKegiatan?->fileLuaranFiles()->exists() ?? false;
        $isMultiple = is_array($this->file('file_luaran'));
        $fileRules = ['file', 'mimes:pdf,doc,docx,zip,rar,jpg,jpeg,png', 'max:51200'];

        return [
            'deskripsi_luaran' => ['required', 'string', 'min:5'],
            'file_luaran' => [
                $hasExistingLuaran ? 'nullable' : 'required',
                ...($isMultiple ? ['array'] : $fileRules),
            ],
            'file_luaran.*' => $isMultiple ? $fileRules : [],
            'submit_action' => ['required', Rule::in(['draft', 'submit'])],
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
            'deskripsi_luaran.required' => 'Deskripsi luaran kegiatan wajib diisi.',
            'deskripsi_luaran.min' => 'Deskripsi luaran minimal 5 karakter.',
            'file_luaran.required' => 'Berkas file luaran wajib diunggah.',
            'file_luaran.mimes' => 'Format file luaran harus PDF, DOC, DOCX, ZIP, RAR, JPG, atau PNG.',
            'file_luaran.max' => 'Ukuran file luaran maksimal adalah 50MB.',
            'submit_action.required' => 'Aksi simpan wajib dipilih.',
            'submit_action.in' => 'Aksi simpan tidak valid.',
        ];
    }
}
