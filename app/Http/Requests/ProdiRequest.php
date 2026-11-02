<?php

namespace App\Http\Requests;

use App\Models\Prodi;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdiRequest extends FormRequest
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
        $prodi = $this->route('prodi');
        $prodiKey = $prodi instanceof Prodi ? $prodi->getKey() : $prodi;

        return [
            'kode_prodi' => [
                'required',
                'string',
                'max:20',
                Rule::unique('prodi', 'kode_prodi')->ignore($prodiKey, 'kode_prodi'),
            ],
            'nama_prodi' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode_prodi.required' => 'Kode prodi wajib diisi.',
            'kode_prodi.unique' => 'Kode prodi sudah digunakan.',
            'nama_prodi.required' => 'Nama prodi wajib diisi.',
        ];
    }
}
