<?php

namespace App\Http\Requests;

use App\Models\Reader;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReaderRequest extends FormRequest
{
    public const NAME_REGEX = '/^[\p{L}\s.\'-]+$/u';

    public const DNI_DIGITS_REGEX = '/^[0-9]{6,20}$/';

    public const PHONE_DIGITS_REGEX = '/^[0-9]{7,15}$/';

    /**
     * Reglas compartidas de identidad (solo letras para nombres/apellidos y solo dígitos para teléfono/DNI).
     *
     * @return array<string, array<int, string>>
     */
    public static function identityRules(bool $withDni = true): array
    {
        $rules = [
            'nombres' => ['required', 'string', 'max:100', 'regex:'.self::NAME_REGEX],
            'apellidos' => ['required', 'string', 'max:100', 'regex:'.self::NAME_REGEX],
            'telefono' => ['nullable', 'string', 'max:15', 'regex:'.self::PHONE_DIGITS_REGEX],
        ];

        if ($withDni) {
            $rules['dni_codigo'] = ['nullable', 'string', 'max:20', 'regex:'.self::DNI_DIGITS_REGEX];
        }

        return $rules;
    }

    /**
     * Mensajes uniformes de validación para campos de identidad.
     *
     * @return array<string, string>
     */
    public static function identityMessages(): array
    {
        return [
            'nombres.regex' => 'El campo nombres solo puede contener letras y espacios (sin números).',
            'apellidos.regex' => 'El campo apellidos solo puede contener letras y espacios (sin números).',
            'dni_codigo.regex' => 'El DNI / documento debe contener solo números (de 6 a 20 dígitos, sin letras).',
            'telefono.regex' => 'El teléfono solo debe contener números (de 7 a 15 dígitos, sin letras).',
        ];
    }

    public function authorize(): bool
    {
        return $this->user()?->esAdmin() ?? false;
    }

    public function rules(): array
    {
        /** @var Reader|null $reader */
        $reader = $this->route('reader');
        $identity = self::identityRules(true);

        return [
            'dni_codigo' => [
                ...$identity['dni_codigo'],
                Rule::unique('readers', 'dni_codigo')->ignore($reader?->id),
            ],
            'nombres' => $identity['nombres'],
            'apellidos' => $identity['apellidos'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('readers', 'email')->ignore($reader?->id),
                Rule::unique('users', 'email')->ignore($reader?->user_id),
            ],
            'telefono' => $identity['telefono'],
            'tipo' => ['required', 'in:estudiante,docente,publico'],
            'estado' => ['required', 'in:activo,suspendido'],
            'password' => ['nullable', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return self::identityMessages();
    }
}
