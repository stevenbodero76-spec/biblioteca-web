<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BookLinkInterconnectorService
{
    /**
     * Convierte enlaces de archive.org/details/{id}, openlibrary.org/... o similares a su visor embebible directo.
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

        // Caso openlibrary.org/isbn/..., /books/OL...M, /works/OL...W o /search?q=...
        if (preg_match('~^https?://(?:www\.)?openlibrary\.org/~i', $trimmed)) {
            return $this->resolverEmbedDesdeOpenLibraryUrl($trimmed);
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
    public function resolverEnlacesLectura(Book $book, bool $resolverOpenLibrary = true): array
    {
        $cleanIsbn = preg_replace('/[^0-9Xx]/', '', (string) $book->isbn);
        $queryTituloAutor = urlencode(trim($book->titulo . ' ' . $book->autor));
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

        $esUrlOpenLibrary = (bool) preg_match('~^https?://(?:www\.)?openlibrary\.org/~i', $principalUrl);
        $embedUrl = ($esUrlOpenLibrary && ! $resolverOpenLibrary)
            ? null
            : $this->convertirAEmbedUrl($principalUrl);

        if (! $embedUrl && $book->esPdfEmbeddable()) {
            $embedUrl = $book->visor_embed_url ?? $book->archivo_pdf;
        }

        if ($embedUrl && ($esUrlOpenLibrary || str_contains($principalUrl, 'archive.org/details/'))) {
            $principalUrl = $embedUrl;
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
        $embedUrl = $this->extraerEmbedArchiveDesdeOpenLibraryPayload($payload);
        $ebooks = $payload['ebooks'] ?? [];
        $readUrl = null;

        if (is_array($ebooks) && ! empty($ebooks[0]) && is_array($ebooks[0])) {
            $readUrl = $ebooks[0]['read_url']
                ?? $ebooks[0]['borrow_url']
                ?? $ebooks[0]['preview_url']
                ?? null;

            if ($readUrl && str_contains($readUrl, 'archive.org/details/')) {
                $readUrl = $this->convertirAEmbedUrl($readUrl) ?? $readUrl;
            }
        }

        if (! $readUrl && $embedUrl) {
            $readUrl = $embedUrl;
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

    /**
     * Resuelve una URL de Open Library (/isbn/{isbn}, /books/OL...M, /works/OL...W, /search?q=...)
     * hacia su visor embebido oficial https://archive.org/embed/{id} cuando existe copia digital en IA.
     */
    private function resolverEmbedDesdeOpenLibraryUrl(string $url): ?string
    {
        $parsed = parse_url($url);
        if (! is_array($parsed)) {
            return null;
        }

        parse_str($parsed['query'] ?? '', $queryParams);
        if (! empty($queryParams['ia']) && preg_match('/^[a-zA-Z0-9._-]+$/', (string) $queryParams['ia'])) {
            return 'https://archive.org/embed/' . $queryParams['ia'];
        }

        $path = $parsed['path'] ?? '';
        $cacheKey = 'ol_embed_url_' . md5($path . '|' . ($queryParams['q'] ?? ''));

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);

            return $cached !== '' ? (string) $cached : null;
        }

        $embedUrl = null;
        $consultaExitosa = false;

        try {
            if (preg_match('~^/isbn/([0-9Xx-]+)~i', $path, $m)) {
                $cleanIsbn = preg_replace('/[^0-9Xx]/', '', $m[1]);
                if ($cleanIsbn !== '') {
                    $res = Http::timeout(2)->acceptJson()->get('https://openlibrary.org/api/books', [
                        'bibkeys' => "ISBN:{$cleanIsbn}",
                        'format' => 'json',
                        'jscmd' => 'data',
                    ]);
                    if ($res->successful()) {
                        $consultaExitosa = true;
                        $payload = $res->json("ISBN:{$cleanIsbn}");
                        if (is_array($payload)) {
                            $embedUrl = $this->extraerEmbedArchiveDesdeOpenLibraryPayload($payload);
                        }
                    }
                }
            } elseif (preg_match('~^/books/(OL[0-9]+M)~i', $path, $m)) {
                $olid = strtoupper($m[1]);
                $res = Http::timeout(2)->acceptJson()->get("https://openlibrary.org/books/{$olid}.json");
                if ($res->successful()) {
                    $consultaExitosa = true;
                    $payload = $res->json();
                    if (is_array($payload)) {
                        $embedUrl = $this->extraerEmbedArchiveDesdeOpenLibraryPayload($payload);
                    }
                }
            } elseif (preg_match('~^/works/(OL[0-9]+W)~i', $path, $m)) {
                $olid = strtoupper($m[1]);
                $res = Http::timeout(2)->acceptJson()->get("https://openlibrary.org/works/{$olid}/editions.json", [
                    'limit' => 5,
                ]);
                if ($res->successful()) {
                    $consultaExitosa = true;
                    foreach ($res->json('entries') ?? [] as $entry) {
                        if (is_array($entry)) {
                            $embedUrl = $this->extraerEmbedArchiveDesdeOpenLibraryPayload($entry);
                            if ($embedUrl) {
                                break;
                            }
                        }
                    }
                }
            } elseif (str_starts_with($path, '/search') && ! empty($queryParams['q'])) {
                $res = Http::timeout(2)->acceptJson()->get('https://openlibrary.org/search.json', [
                    'q' => (string) $queryParams['q'],
                    'fields' => 'ia',
                    'limit' => 3,
                ]);
                if ($res->successful()) {
                    $consultaExitosa = true;
                    foreach ($res->json('docs') ?? [] as $doc) {
                        if (! empty($doc['ia'][0]) && preg_match('/^[a-zA-Z0-9._-]+$/', (string) $doc['ia'][0])) {
                            $embedUrl = 'https://archive.org/embed/' . $doc['ia'][0];
                            break;
                        }
                    }
                }
            }
        } catch (\Throwable) {
            return null;
        }

        if ($embedUrl) {
            Cache::put($cacheKey, $embedUrl, now()->addDays(7));
        } elseif ($consultaExitosa) {
            Cache::put($cacheKey, '', now()->addHours(6));
        }

        return $embedUrl;
    }

    /**
     * Extrae la URL embebida de Internet Archive desde un payload JSON de Open Library.
     *
     * @param  array<string, mixed>  $payload
     */
    private function extraerEmbedArchiveDesdeOpenLibraryPayload(array $payload): ?string
    {
        $iaId = $payload['identifiers']['internet_archive'][0]
            ?? $payload['ocaid']
            ?? null;

        if (is_string($iaId) && trim($iaId) !== '' && preg_match('/^[a-zA-Z0-9._-]+$/', trim($iaId))) {
            return 'https://archive.org/embed/' . trim($iaId);
        }

        $ebooks = $payload['ebooks'] ?? [];
        if (is_array($ebooks)) {
            foreach ($ebooks as $ebook) {
                if (! is_array($ebook)) {
                    continue;
                }
                foreach (['read_url', 'borrow_url', 'preview_url'] as $key) {
                    $candidate = (string) ($ebook[$key] ?? '');
                    if ($candidate !== '' && preg_match('~archive\.org/(?:details|embed)/([^/?#]+)~i', $candidate, $m)) {
                        return 'https://archive.org/embed/' . $m[1];
                    }
                }
            }
        }

        return null;
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
                $enlaces = $this->resolverEnlacesLectura($item, false);
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
