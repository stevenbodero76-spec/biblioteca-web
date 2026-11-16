<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CheckoutOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $rules = [
            'metodo_pago' => ['required', 'in:yape_plin,tarjeta,mostrador,membresia'],
            'codigo_operacion' => ['nullable', 'string', 'max:60'],
            'tipo_entrega' => ['required', 'in:tienda,delivery'],
            'direccion_entrega' => ['nullable', 'required_if:tipo_entrega,delivery', 'string', 'max:255'],
            'usar_karma' => ['nullable', 'boolean'],
        ];

        if ($this->is('api/*')) {
            $rules['items'] = ['required', 'array', 'min:1', 'max:20'];
            $rules['items.*.book_id'] = ['required', 'integer', 'exists:books,id'];
            $rules['items.*.tipo_operacion'] = ['required', 'in:venta,alquiler'];
            $rules['items.*.cantidad'] = ['nullable', 'integer', 'min:1', 'max:20'];
            $rules['items.*.duracion_dias'] = ['nullable', 'integer', 'in:7,14,30'];
        }

        if ($this->input('metodo_pago') === 'tarjeta') {
            $rules['card_number'] = ['required', 'string', 'min:13', 'max:24'];
            $rules['card_expiry'] = ['required', 'regex:/^(0[1-9]|1[0-2])\/(\d{2}|\d{4})$/'];
            $rules['card_cvv'] = ['required', 'regex:/^\d{3,4}$/'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('metodo_pago') !== 'tarjeta') {
                return;
            }

            $digits = preg_replace('/\D/', '', (string) $this->input('card_number', ''));
            if (! preg_match('/^\d{13,19}$/', (string) $digits)) {
                $validator->errors()->add('card_number', 'Número de tarjeta inválido en pasarela sandbox (debe tener entre 13 y 19 dígitos).');
            }

            $expiry = (string) $this->input('card_expiry', '');
            if (preg_match('/^(0[1-9]|1[0-2])\/(\d{2}|\d{4})$/', $expiry, $m)) {
                $month = (int) $m[1];
                $year = strlen($m[2]) === 2 ? (2000 + (int) $m[2]) : (int) $m[2];
                $vence = now()->createFromDate($year, $month, 1)->endOfMonth();
                if ($vence->isPast()) {
                    $validator->errors()->add('card_expiry', 'La tarjeta ingresada se encuentra vencida.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'direccion_entrega.required_if' => 'Ingresa la dirección de envío en Lima / Perú para modalidad delivery.',
            'card_number.required' => 'Ingresa el número de tarjeta para pagar en pasarela.',
            'card_expiry.required' => 'Ingresa la fecha de vencimiento (MM/AA).',
            'card_expiry.regex' => 'El vencimiento debe tener formato MM/AA o MM/AAAA.',
            'card_cvv.required' => 'Ingresa el código de seguridad CVV.',
            'card_cvv.regex' => 'El CVV debe contener 3 o 4 dígitos numéricos.',
        ];
    }
}
