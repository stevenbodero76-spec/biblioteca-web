<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Collection;

class BookLinkInterconnectorService
{
    /**
     * Construye todos los enlaces de lectura abierta e interconectada para una obra.
     *
     * @return array{
     *     principal_url: string,
     *     embed_url: string|null,
     *     es_embebible: bool,
     *     fuentes: array<int, array{nombre: string, tipo: string, url: string, icono: string}>
     * }
     */
    public function resolverEnlacesLectura(Book $book): array
    {
        $cleanIsbn = preg_replace('/[^0-9Xx]/', '', (string) $book->isbn);
        $queryTituloAutor = urlencode(trim($book->titulo.' '.$book->autor));
        $queryTitulo = urlencode(trim($book->titulo));

        $openLibraryUrl = $cleanIsbn !== ''
            ? "https://openlibrary.org/isbn/{$cleanIsbn}"
            : "https://openlibrary.org/search?q={$queryTituloAutor}";

        $internetArchiveUrl = "https://archive.org/details/texts?tab=collection&query={$queryTituloAutor}";
        $gutenbergUrl = "https://www.gutenberg.org/ebooks/search/?query={$queryTitulo}";
        $cervantesUrl = "https://www.cervantesvirtual.com/buscador/?q={$queryTitulo}";

        $principalUrl = trim((string) $book->archivo_pdf) !== ''
            ? (string) $book->archivo_pdf
            : $openLibraryUrl;

        $embedUrl = null;
        if ($book->esPdfEmbeddable() || str_contains($principalUrl, 'archive.org/embed/')) {
            $embedUrl = $principalUrl;
        }

        $fuentes = [
            [
                'nombre' => 'Open Library',
                'tipo' => 'Lectura Digital / Préstamo Público',
                'url' => $openLibraryUrl,
                'icono' => '📖',
            ],
            [
                'nombre' => 'Internet Archive',
                'tipo' => 'Visor de Páginas Completas',
                'url' => $internetArchiveUrl,
                'icono' => '🏛️',
            ],
            [
                'nombre' => 'Project Gutenberg',
                'tipo' => 'Texto Completo / EPUB / HTML',
                'url' => $gutenbergUrl,
                'icono' => '🌐',
            ],
            [
                'nombre' => 'MangaPlus',
                'tipo' => 'Capítulos Digitales Gratuitos',
                'url' => str_contains($principalUrl, 'mangaplus') ? $principalUrl : 'https://mangaplus.shueisha.co.jp/',
                'icono' => '🎌',
            ],
            [
                'nombre' => 'Cervantes Virtual',
                'tipo' => 'Clásicos en Español',
                'url' => $cervantesUrl,
                'icono' => '🖋️',
            ],
        ];

        return [
            'principal_url' => $principalUrl,
            'embed_url' => $embedUrl,
            'es_embebible' => $embedUrl !== null,
            'fuentes' => $fuentes,
        ];
    }

    /**
     * Extrae enlaces de lectura directa desde el payload de Open Library por ISBN.
     *
     * @param  array<string, mixed>  $payload
     * @return array{archivo_pdf: string, fuentes: array<int, array<string, string>>}
     */
    public function extraerEnlacesDesdeOpenLibrary(string $cleanIsbn, array $payload): array
    {
        $ebooks = $payload['ebooks'] ?? [];
        $readUrl = null;

        if (is_array($ebooks) && ! empty($ebooks[0]) && is_array($ebooks[0])) {
            $readUrl = $ebooks[0]['read_url']
                ?? $ebooks[0]['borrow_url']
                ?? $ebooks[0]['preview_url']
                ?? null;
        }

        if (! $readUrl && ! empty($payload['identifiers']['internet_archive'][0])) {
            $iaId = $payload['identifiers']['internet_archive'][0];
            $readUrl = "https://archive.org/embed/{$iaId}";
        }

        if (! $readUrl) {
            $readUrl = $payload['url'] ?? "https://openlibrary.org/isbn/{$cleanIsbn}";
        }

        $titulo = (string) ($payload['title'] ?? '');

        return [
            'archivo_pdf' => (string) $readUrl,
            'fuentes' => [
                [
                    'nombre' => 'Open Library Reader',
                    'url' => (string) $readUrl,
                ],
                [
                    'nombre' => 'Internet Archive',
                    'url' => 'https://archive.org/details/texts?tab=collection&query='.urlencode($titulo ?: $cleanIsbn),
                ],
                [
                    'nombre' => 'Project Gutenberg',
                    'url' => 'https://www.gutenberg.org/ebooks/search/?query='.urlencode($titulo ?: $cleanIsbn),
                ],
            ],
        ];
    }

    public function construirUrlLecturaPorDefecto(string $isbn, string $titulo): string
    {
        $cleanIsbn = preg_replace('/[^0-9Xx]/', '', $isbn);
        if ($cleanIsbn !== '') {
            return "https://openlibrary.org/isbn/{$cleanIsbn}";
        }

        return 'https://openlibrary.org/search?q='.urlencode($titulo);
    }

    /**
     * Obtiene una red de libros interconectados por autor, palabras clave y categoría,
     * adjuntando a cada uno su enlace directo de lectura.
     *
     * @return Collection<int, Book>
     */
    public function obtenerLibrosInterconectados(Book $book, int $limite = 4): Collection
    {
        $palabrasClave = collect(preg_split('/\s+/u', mb_strtolower($book->titulo)))
            ->map(fn ($w) => preg_replace('/[^\p{L}\p{N}]/u', '', $w))
            ->filter(fn ($w) => mb_strlen($w) >= 4 && ! in_array($w, ['para', 'como', 'este', 'esta', 'edición', 'vol'], true))
            ->take(3)
            ->values();

        $candidatos = Book::with('category')
            ->where('id', '!=', $book->id)
            ->where(function ($q) use ($book, $palabrasClave) {
                $q->where('category_id', $book->category_id)
                    ->orWhere('autor', $book->autor);

                foreach ($palabrasClave as $palabra) {
                    $q->orWhere('titulo', 'like', "%{$palabra}%")
                        ->orWhere('sinopsis', 'like', "%{$palabra}%");
                }
            })
            ->get();

        return $candidatos
            ->sortByDesc(function (Book $item) use ($book, $palabrasClave) {
                $score = 0;
                if (mb_strtolower($item->autor) === mb_strtolower($book->autor)) {
                    $score += 50;
                }
                if ((int) $item->category_id === (int) $book->category_id) {
                    $score += 30;
                }
                foreach ($palabrasClave as $palabra) {
                    if (str_contains(mb_strtolower($item->titulo.' '.$item->sinopsis), $palabra)) {
                        $score += 10;
                    }
                }
                if ($item->estaDisponible()) {
                    $score += 5;
                }

                return $score;
            })
            ->take($limite)
            ->values()
            ->map(function (Book $item) {
                $enlaces = $this->resolverEnlacesLectura($item);
                $item->setAttribute('enlace_lectura_directa', $enlaces['principal_url']);
                $item->setAttribute('fuentes_lectura', $enlaces['fuentes']);

                return $item;
            });
    }
}
