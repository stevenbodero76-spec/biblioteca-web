<?php

namespace App\Http\Requests;

use App\Models\Reader;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdmin() ?? false;
    }

    public function rules(): array
    {
        /** @var Reader|null $reader */
        $reader = $this->route('reader');

        return [
            'dni_codigo' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('readers', 'dni_codigo')->ignore($reader?->id),
            ],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('readers', 'email')->ignore($reader?->id),
                Rule::unique('users', 'email')->ignore($reader?->user_id),
            ],
            'telefono' => ['nullable', 'string', 'max:20'],
            'tipo' => ['required', 'in:estudiante,docente,publico'],
            'estado' => ['required', 'in:activo,suspendido'],
            'password' => ['nullable', 'string', 'min:6'],
        ];
    }
}
