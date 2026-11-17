<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        // Seguridad BOLA/IDOR: Si es cliente, forzamos siempre su propio reader_id
        if ($this->user()?->esCliente()) {
            $this->merge([
                'reader_id' => $this->user()->reader?->id,
            ]);
        }

        // Reserva rápida en 1 clic por duración (7, 14 o 30 días)
        if ($this->filled('duracion_dias') && in_array((int) $this->input('duracion_dias'), [7, 14, 30], true)) {
            $dias = (int) $this->input('duracion_dias');
            $this->merge([
                'fecha_prestamo' => now()->toDateString(),
                'fecha_devolucion_esperada' => now()->addDays($dias)->toDateString(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'reader_id' => ['required', 'integer', 'exists:readers,id'],
            'duracion_dias' => ['nullable', 'integer', 'in:7,14,30'],
            'fecha_prestamo' => ['required', 'date', 'after_or_equal:' . now()->subDay()->toDateString(), 'before_or_equal:' . now()->addDays(7)->toDateString()],
            'fecha_devolucion_esperada' => ['required', 'date', 'after_or_equal:fecha_prestamo'],
            'metodo_pago' => ['nullable', 'in:gratuito,membresia,yape_plin,mostrador'],
            'codigo_operacion' => ['nullable', 'string', 'max:60'],
            'pago_verificado_admin' => ['nullable', 'boolean'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->user()?->esCliente() && ! $this->user()->reader) {
                $validator->errors()->add('reader_id', 'Tu cuenta no tiene una ficha de lector activa vinculada.');
            }

            if ($this->filled('fecha_prestamo') && $this->filled('fecha_devolucion_esperada')) {
                try {
                    $inicio = now()->parse($this->input('fecha_prestamo'));
                    $fin = now()->parse($this->input('fecha_devolucion_esperada'));
                    if ($inicio->diffInDays($fin) > 30) {
                        $validator->errors()->add('fecha_devolucion_esperada', 'El plazo máximo permitido por préstamo es de 30 días.');
                    }
                } catch (\Throwable) {
                    // Cubierto por regla 'date'
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'reader_id.required' => 'Debes seleccionar un lector válido o tener una ficha de cliente activa.',
            'fecha_prestamo.after_or_equal' => 'La fecha de préstamo no puede ser anterior a ayer.',
            'fecha_devolucion_esperada.after_or_equal' => 'La fecha de devolución debe ser igual o posterior a la fecha de préstamo.',
        ];
    }
}
