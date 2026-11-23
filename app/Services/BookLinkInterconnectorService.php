<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BookLinkInterconnectorService
{
    /**
     * Convierte enlaces de archive.org/details/{id} o similares a su visor embebible directo.
     */
    public function convertirAEmbedUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $trimmed = trim($url);

        // Ya es un embed directo de archive.org
        if (str_contains($trimmed, 'archive.org/embed/')) {
            return $trimmed;
        }

        // Caso archive.org/details/{identifier}
        if (preg_match('~archive\.org/details/([^/?#]+)~i', $trimmed, $matches)) {
            $identifier = $matches[1];
            if (! in_array($identifier, ['texts', 'books', 'audio', 'movies'], true)) {
                return "https://archive.org/embed/{$identifier}";
            }
        }

        // Caso archivo PDF local o en almacenamiento
        if (str_starts_with($trimmed, '/storage/') || str_ends_with(strtolower(parse_url($trimmed, PHP_URL_PATH) ?? ''), '.pdf')) {
            return $trimmed;
        }

        return null;
    }

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
        $queryTituloAutor = urlencode(trim($book->titulo . ' ' . $book->autor));
        $queryTitulo = urlencode(trim($book->titulo));

        $openLibraryUrl = $cleanIsbn !== ''
            ? "https://openlibrary.org/isbn/{$cleanIsbn}"
            : "https://openlibrary.org/search?q={$queryTituloAutor}";

        $internetArchiveUrl = "https://archive.org/details/texts?tab=collection&query={$queryTituloAutor}";
        $gutenbergUrl = "https://www.gutenberg.org/ebooks/search/?query={$queryTitulo}";
        $googleBooksUrl = $cleanIsbn !== ''
            ? "https://www.google.com/search?tbm=bks&q=isbn:{$cleanIsbn}"
            : "https://www.google.com/search?tbm=bks&q={$queryTituloAutor}";

        $principalUrl = trim((string) $book->archivo_pdf) !== ''
            ? (string) $book->archivo_pdf
            : $openLibraryUrl;

        $embedUrl = $this->convertirAEmbedUrl($principalUrl);

        if (! $embedUrl && $book->esPdfEmbeddable()) {
            $embedUrl = $book->visor_embed_url ?? $book->archivo_pdf;
        }

        if ($embedUrl && str_contains($principalUrl, 'archive.org/details/')) {
            $principalUrl = $embedUrl;
        }

        $fuentes = [
            [
                'nombre' => 'Open Library · Lectura Abierta',
                'tipo' => 'Lectura Digital / Préstamo Público',
                'url' => $openLibraryUrl,
                'icono' => '📖',
            ],
            [
                'nombre' => 'Internet Archive · Bóveda de Textos',
                'tipo' => 'Visor de Páginas Completas',
                'url' => $internetArchiveUrl,
                'icono' => '🏛️',
            ],
            [
                'nombre' => 'Project Gutenberg · Dominio Público',
                'tipo' => 'Texto Completo / EPUB / HTML',
                'url' => $gutenbergUrl,
                'icono' => '🌐',
            ],
            [
                'nombre' => 'Google Books · Vista Previa',
                'tipo' => 'Fragmentos y Capítulos',
                'url' => $googleBooksUrl,
                'icono' => '🔎',
            ],
        ];

        $categoriaNombre = mb_strtolower($book->category?->nombre ?? '');
        if (str_contains($categoriaNombre, 'cómic') || str_contains($categoriaNombre, 'manga')) {
            array_unshift($fuentes, [
                'nombre' => 'MangaPlus · Lectura Oficial',
                'tipo' => 'Capítulos Digitales Gratuitos',
                'url' => str_contains($principalUrl, 'mangaplus') ? $principalUrl : 'https://mangaplus.shueisha.co.jp/',
                'icono' => '🎌',
            ]);
        } elseif (str_contains($categoriaNombre, 'literatura') || str_contains($categoriaNombre, 'historia')) {
            $fuentes[] = [
                'nombre' => 'Biblioteca Virtual Miguel de Cervantes',
                'tipo' => 'Clásicos en Español',
                'url' => "https://www.cervantesvirtual.com/buscador/?q={$queryTitulo}",
                'icono' => '🖋️',
            ];
        }

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
                    'url' => 'https://archive.org/details/texts?tab=collection&query=' . urlencode($titulo ?: $cleanIsbn),
                ],
                [
                    'nombre' => 'Project Gutenberg',
                    'url' => 'https://www.gutenberg.org/ebooks/search/?query=' . urlencode($titulo ?: $cleanIsbn),
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

        return 'https://openlibrary.org/search?q=' . urlencode($titulo);
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
            ->orderByRaw(
                'CASE WHEN LOWER(autor) = ? THEN 50 ELSE 0 END + CASE WHEN category_id = ? THEN 30 ELSE 0 END + CASE WHEN stock_disponible > 0 THEN 5 ELSE 0 END DESC',
                [mb_strtolower((string) $book->autor), (int) $book->category_id]
            )
            ->limit($limite)
            ->get();

        return $candidatos
            ->map(function (Book $item) {
                $enlaces = $this->resolverEnlacesLectura($item);
                $item->setAttribute('enlace_lectura_directa', $enlaces['principal_url']);
                $item->setAttribute('fuentes_lectura', $enlaces['fuentes']);

                return $item;
            });
    }

    /**
     * Consulta en tiempo real las APIs públicas y gratuitas de Open Library y Project Gutenberg (Gutendex).
     *
     * @return array<int, array{
     *     titulo: string,
     *     autor: string,
     *     editorial: string,
     *     anio_publicacion: int|null,
     *     isbn: string|null,
     *     portada_url: string|null,
     *     archivo_pdf: string,
     *     fuente: string,
     *     fuente_icono: string,
     *     es_embebible: bool,
     *     sinopsis: string
     * }>
     */
    public function buscarEnRedesExternas(string $termino, int $limitePorFuente = 4): array
    {
        $termino = trim($termino);
        if (mb_strlen($termino) < 2) {
            return [];
        }

        $cacheKey = 'ext_search_' . md5(mb_strtolower($termino)) . '_' . $limitePorFuente;

        return Cache::remember($cacheKey, 3600, function () use ($termino, $limitePorFuente) {
            $resultados = [];

            // 1. Consulta Open Library Search API
            try {
                $responseOl = Http::timeout(4)
                    ->acceptJson()
                    ->get('https://openlibrary.org/search.json', [
                        'q' => $termino,
                        'limit' => $limitePorFuente,
                        'fields' => 'title,author_name,isbn,first_publish_year,cover_i,ia,key,publisher',
                    ]);

                if ($responseOl->successful()) {
                    $docs = $responseOl->json('docs') ?? [];
                    foreach ($docs as $doc) {
                        $iaId = ! empty($doc['ia'][0]) ? (string) $doc['ia'][0] : null;
                        $isbn = ! empty($doc['isbn'][0]) ? (string) $doc['isbn'][0] : null;
                        $coverId = ! empty($doc['cover_i']) ? (int) $doc['cover_i'] : null;

                        $portada = $coverId
                            ? "https://covers.openlibrary.org/b/id/{$coverId}-M.jpg"
                            : ($isbn ? "https://covers.openlibrary.org/b/isbn/{$isbn}-M.jpg" : null);

                        $archivoPdf = $iaId
                            ? "https://archive.org/embed/{$iaId}"
                            : ($isbn ? "https://openlibrary.org/isbn/{$isbn}" : 'https://openlibrary.org' . ($doc['key'] ?? ''));

                        $autores = ! empty($doc['author_name']) ? implode(', ', (array) $doc['author_name']) : 'Autor Desconocido';
                        $editoriales = ! empty($doc['publisher'])
                            ? (is_array($doc['publisher']) ? implode(', ', array_slice($doc['publisher'], 0, 2)) : (string) $doc['publisher'])
                            : 'Open Library Digital';

                        $resultados[] = [
                            'titulo' => (string) ($doc['title'] ?? 'Obra sin título'),
                            'autor' => $autores,
                            'editorial' => $editoriales,
                            'anio_publicacion' => ! empty($doc['first_publish_year']) ? (int) $doc['first_publish_year'] : null,
                            'isbn' => $isbn,
                            'portada_url' => $portada,
                            'archivo_pdf' => $archivoPdf,
                            'fuente' => 'Open Library',
                            'fuente_icono' => '📖',
                            'es_embebible' => $iaId !== null,
                            'sinopsis' => 'Obra disponible en la red abierta de Open Library / Internet Archive con acceso de lectura pública.',
                        ];
                    }
                }
            } catch (\Throwable) {
                // Falla silenciosa para proteger la disponibilidad en caídas externas
            }

            // 2. Consulta Gutendex (Project Gutenberg) API
            try {
                $responseGuten = Http::timeout(4)
                    ->acceptJson()
                    ->get('https://gutendex.com/books', [
                        'search' => $termino,
                    ]);

                if ($responseGuten->successful()) {
                    $gutenResults = array_slice($responseGuten->json('results') ?? [], 0, $limitePorFuente);
                    foreach ($gutenResults as $item) {
                        $formats = $item['formats'] ?? [];
                        $htmlUrl = $formats['text/html'] ?? ($formats['text/html; charset=utf-8'] ?? null);
                        $epubUrl = $formats['application/epub+zip'] ?? null;
                        $coverUrl = $formats['image/jpeg'] ?? null;

                        $urlLectura = $htmlUrl ?: ($epubUrl ?: "https://www.gutenberg.org/ebooks/{$item['id']}");
                        $autores = collect($item['authors'] ?? [])->pluck('name')->implode(', ') ?: 'Project Gutenberg';

                        $resultados[] = [
                            'titulo' => (string) ($item['title'] ?? 'Obra clásica'),
                            'autor' => $autores,
                            'editorial' => 'Project Gutenberg (Dominio Público)',
                            'anio_publicacion' => null,
                            'isbn' => 'PG-' . $item['id'],
                            'portada_url' => $coverUrl,
                            'archivo_pdf' => $urlLectura,
                            'fuente' => 'Project Gutenberg',
                            'fuente_icono' => '🏛️',
                            'es_embebible' => true,
                            'sinopsis' => 'Texto completo en dominio público distribuido libremente por el Proyecto Gutenberg.',
                        ];
                    }
                }
            } catch (\Throwable) {
                // Falla silenciosa
            }

            return $resultados;
        });
    }
}
