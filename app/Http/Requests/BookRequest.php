<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'idioma' => $this->filled('idioma') ? $this->input('idioma') : 'es',
            'licencia' => $this->filled('licencia') ? $this->input('licencia') : 'acceso_abierto',
        ]);
    }

    /**
     * Regla reutilizable para validar ubicaciones de portada/PDF (http/https o /storage/).
     */
    public static function safeMediaLocationRule(bool $allowLocalStorage = true): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($allowLocalStorage): void {
            if ($value === null || $value === '') {
                return;
            }

            $str = (string) $value;
            if ($allowLocalStorage && str_starts_with($str, '/storage/') && ! str_contains($str, '..')) {
                return;
            }

            $scheme = strtolower((string) parse_url($str, PHP_URL_SCHEME));
            if (filter_var($str, FILTER_VALIDATE_URL) !== false && in_array($scheme, ['http', 'https'], true)) {
                return;
            }

            $mensaje = $allowLocalStorage
                ? "El campo {$attribute} debe ser una URL válida http/https o un archivo subido al almacenamiento local."
                : "El campo {$attribute} debe ser una URL válida con protocolo http o https.";

            $fail($mensaje);
        };
    }

    public function rules(): array
    {
        /** @var Book|null $book */
        $book = $this->route('book');
        $safeMediaLocation = self::safeMediaLocationRule(true);

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'isbn' => [
                'required',
                'string',
                'max:30',
                Rule::unique('books', 'isbn')->ignore($book?->id),
            ],
            'titulo' => ['required', 'string', 'max:200'],
            'autor' => ['required', 'string', 'max:150'],
            'editorial' => ['nullable', 'string', 'max:100'],
            'anio_publicacion' => ['nullable', 'integer', 'min:1000', 'max:'.(date('Y') + 1)],
            'formato' => ['required', 'in:fisico,digital,ambos'],
            'idioma' => ['required', 'in:es,en'],
            'licencia' => ['required', 'in:dominio_publico,creative_commons,acceso_abierto'],
            'sinopsis' => ['nullable', 'string', 'max:5000'],
            'portada_url' => ['nullable', 'string', 'max:500', $safeMediaLocation],
            'archivo_pdf' => ['nullable', 'string', 'max:500', $safeMediaLocation],
            'portada_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:15360'],
            'ubicacion_estante' => ['nullable', 'string', 'max:50'],
            'stock_total' => ['required', 'integer', 'min:1', 'max:500'],
            'stock_disponible' => ['required', 'integer', 'min:0', 'lte:stock_total'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Book|null $book */
            $book = $this->route('book');

            if (! $book) {
                return;
            }

            $prestamosActivos = $book->prestamosActivosCount();
            $stockTotal = (int) $this->input('stock_total', 0);
            $stockDisponible = (int) $this->input('stock_disponible', 0);

            if ($stockTotal < $prestamosActivos) {
                $validator->errors()->add(
                    'stock_total',
                    "No puedes reducir el stock total por debajo de {$prestamosActivos} porque existen préstamos activos en circulación."
                );
            }

            $maxDisponible = max(0, $stockTotal - $prestamosActivos);
            if ($stockDisponible > $maxDisponible) {
                $validator->errors()->add(
                    'stock_disponible',
                    "Con {$prestamosActivos} ejemplar(es) prestado(s) actualmente, el stock disponible máximo permitido es {$maxDisponible}."
                );
            }
        });
    }
}
