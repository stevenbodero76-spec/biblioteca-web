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
        if (preg_match('~archive\.org/details/([^/?#]+)~i', $trimmed,$matches)) {
            $identifier =$matches[1];
            if (! in_array($identifier, ['texts', 'books', 'audio', 'movies'], true)) {
                return "https://archive.org/embed/{$identifier}";
            }
        }

        // Caso openlibrary.org/isbn/..., /books/OL...M, /works/OL...W o /search?q=...
        if (preg_match('~^https?://(?:www\.)?openlibrary\.org/~i', $trimmed)) {
            return $this->resolverEmbedDesdeOpenLibraryUrl($trimmed);
        }

        // Caso archivo PDF local o en almacenamiento
        if (str_starts_with($trimmed, '/storage/') \vert{}\vert{} str_ends_with(strtolower(parse_url($trimmed, PHP_URL_PATH) ?? ''), '.pdf')) {
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
    public function resolverEnlacesLectura(Book $book, bool$resolverOpenLibrary = true): array
    {
        $cleanIsbn = preg_replace('/[^0-9Xx]/', '', (string) $book->isbn);$queryTituloAutor = urlencode(trim($book->titulo.' '.$book->autor));
        $queryTitulo = urlencode(trim($book->titulo));

        $openLibraryUrl =$cleanIsbn !== ''
            ? "https://openlibrary.org/isbn/{$cleanIsbn}"
            : "https://openlibrary.org/search?q={$queryTituloAutor}";

        $internetArchiveUrl = "https://archive.org/details/texts?tab=collection&query={$queryTituloAutor}";
        $gutenbergUrl = "https://www.gutenberg.org/ebooks/search/?query={$queryTitulo}";
        $cervantesUrl = "https://www.cervantesvirtual.com/buscador/?q={$queryTitulo}";

        $principalUrl = trim((string)$book->archivo_pdf) !== ''
            ? (string) $book->archivo_pdf
            : $openLibraryUrl;

        $esUrlOpenLibrary = (bool) preg_match('~^https?://(?:www\.)?openlibrary\.org/~i', $principalUrl);$embedUrl = ($esUrlOpenLibrary && !$resolverOpenLibrary)
            ? null
            : $this->convertirAEmbedUrl($principalUrl);

        if (! $embedUrl && $book->esPdfEmbeddable()) {$embedUrl = $book->visor_embed_url ?? $book->archivo_pdf;
        }

        if ($embedUrl && ($esUrlOpenLibrary \vert{}\vert{} str_contains($principalUrl, 'archive.org/details/'))) {
            $principalUrl =$embedUrl;
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
                'url' => str_contains($principalUrl, 'mangaplus') ?$principalUrl : 'https://mangaplus.shueisha.co.jp/',
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
    public function extraerEnlacesDesdeOpenLibrary(string $cleanIsbn, array$payload): array
    {
        $embedUrl =$this->extraerEmbedArchiveDesdeOpenLibraryPayload($payload);$ebooks = $payload['ebooks'] ?? [];$readUrl = null;

        if (is_array($ebooks) && ! empty($ebooks[0]) && is_array($ebooks[0])) {
            $readUrl =$ebooks[0]['read_url']
                ?? $ebooks[0]['borrow_url']
                ?? $ebooks[0]['preview_url']
                ?? null;

            if ($readUrl && str_contains($readUrl, 'archive.org/details/')) {
                $readUrl =$this->convertirAEmbedUrl($readUrl) ?? $readUrl;
            }
        }

        if (! $readUrl &&$embedUrl) {
            $readUrl =$embedUrl;
        }

        if (! $readUrl) {$readUrl = $payload['url'] ?? "https://openlibrary.org/isbn/{$cleanIsbn}";
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

        parse_str($parsed['query'] ?? '',$queryParams);
        if (! empty($queryParams['ia']) && preg_match('/^[a-zA-Z0-9._-]+$/', (string)$queryParams['ia'])) {
            return 'https://archive.org/embed/' . $queryParams['ia'];
        }

        $path = $parsed['path'] ?? '';$cacheKey = 'ol_embed_url_' . md5($path . '\vert{}' . ($queryParams['q'] ?? ''));

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);

            return $cached !== '' ? (string)$cached : null;
        }

        $embedUrl = null;
        $consultaExitosa = false;

        try {
            if (preg_match('~^/isbn/([0-9Xx-]+)~i', $path,$m)) {
                $cleanIsbn = preg_replace('/[^0-9Xx]/', '',$m[1]);
                if ($cleanIsbn !== '') {$res = Http::timeout(2)->acceptJson()->get('https://openlibrary.org/api/books', [
                        'bibkeys' => "ISBN:{$cleanIsbn}",
                        'format' => 'json',
                        'jscmd' => 'data',
                    ]);
                    if ($res->successful()) {$consultaExitosa = true;
                        $payload = $res->json("ISBN:{$cleanIsbn}");
                        if (is_array($payload)) {$embedUrl = $this->extraerEmbedArchiveDesdeOpenLibraryPayload($payload);
                        }
                    }
                }
            } elseif (preg_match('~^/books/(OL[0-9]+M)~i', $path,$m)) {
                $olid = strtoupper($m[1]);
                $res = Http::timeout(2)->acceptJson()->get("https://openlibrary.org/books/{$olid}.json");
                if ($res->successful()) {$consultaExitosa = true;
                    $payload =$res->json();
                    if (is_array($payload)) {$embedUrl = $this->extraerEmbedArchiveDesdeOpenLibraryPayload($payload);
                    }
                }
            } elseif (preg_match('~^/works/(OL[0-9]+W)~i', $path,$m)) {
                $olid = strtoupper($m[1]);
                $res = Http::timeout(2)->acceptJson()->get("https://openlibrary.org/works/{$olid}/editions.json", [
                    'limit' => 5,
                ]);
                if ($res