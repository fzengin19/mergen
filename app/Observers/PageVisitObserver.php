<?php

namespace App\Observers;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlObservers\CrawlObserver;

class PageVisitObserver extends CrawlObserver
{
    private array $results = [];
    private ?UriInterface $originalUrl;
    private bool $extractLinks;
    private string $baseUrl;

    public function __construct(?UriInterface $originalUrl = null, bool $extractLinks = false, string $baseUrl = '')
    {
        $this->originalUrl = $originalUrl;
        $this->extractLinks = $extractLinks;
        $this->baseUrl = $baseUrl;
        $this->results = [
            'crawled_urls' => [],
            'page_data' => null,
            'errors' => [],
            'finished_at' => null,
        ];
    }

    public function willCrawl(UriInterface $url, ?string $linkText): void
    {
        Log::info('[VisitPageTool][Observer] willCrawl', [
            'url' => (string) $url,
            'linkText' => $linkText,
        ]);
        $this->results['crawled_urls'][] = (string) $url;
    }

    public function crawled(
        UriInterface $url,
        ResponseInterface $response,
        ?UriInterface $foundOnUrl = null,
        ?string $linkText = null
    ): void {
        Log::info('[VisitPageTool][Observer] crawled', [
            'url' => (string) $url,
            'status_code' => $response->getStatusCode(),
        ]);
        $html = (string) $response->getBody();
        $content = $this->extractContent($html, (string) $url); // extractContent metodu artık hata fırlatmayacak
        $this->results['page_data'] = [
            'url' => (string) $url,
            'title' => $content['title'],
            'meta_description' => $content['meta_description'],
            'body' => $content['body'],
            'headings' => $content['headings'],
            'excerpt' => $content['excerpt'],
            'links' => $content['links'] ?? [],
        ];
    }

    public function crawlFailed(
        UriInterface $url,
        RequestException $requestException,
        ?UriInterface $foundOnUrl = null,
        ?string $linkText = null
    ): void {
        Log::error('[VisitPageTool][Observer] crawlFailed', [
            'url' => (string) $url,
            'message' => $requestException->getMessage(),
        ]);
        $this->results['errors'][] = [
            'url' => (string) $url,
            'message' => $requestException->getMessage(),
        ];
    }

    public function finishedCrawling(): void
    {
        $this->results['finished_at'] = now()->toISOString();
        Log::info('[VisitPageTool][Observer] finishedCrawling');
    }



    public function getResults(): array
    {
        return $this->results;
    }

    // --- EN ÖNEMLİ DEĞİŞİKLİK BURADA ---
    private function extractContent(string $html, string $baseUrl = ''): array
    {
        // Hatalı HTML'in kritik istisnalara yol açmasını engelle
        libxml_use_internal_errors(true);

        $doc = new \DOMDocument();
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        // Hata kontrolünü tekrar eski haline getir
        libxml_clear_errors();
        // --- DEĞİŞİKLİK SONU ---

        $title = $doc->getElementsByTagName('title')->item(0)?->nodeValue ?? '';
        $metaDesc = '';
        foreach ($doc->getElementsByTagName('meta') as $meta) {
            if (strtolower($meta->getAttribute('name')) === 'description') {
                $metaDesc = $meta->getAttribute('content');
            }
        }

        $headings = [];
        foreach (['h1','h2','h3'] as $tag) {
            foreach ($doc->getElementsByTagName($tag) as $node) {
                if (trim($node->textContent) !== '') {
                    $headings[] = trim($node->textContent);
                }
            }
        }

        // Script, style gibi gereksiz etiketleri temizle
        $xpath = new \DOMXPath($doc);
        foreach ($xpath->query('//script | //style | //nav | //footer | //aside') as $node) {
            $node->parentNode->removeChild($node);
        }

        $bodyText = strip_tags($doc->saveHTML());
        $bodyText = preg_replace('/\s+/', ' ', $bodyText);
        $bodyText = trim($bodyText);

        $excerpt = $metaDesc !== '' ? $metaDesc : mb_substr($bodyText, 0, 400, 'UTF-8');

        $result = [
            'title' => trim($title),
            'meta_description' => trim($metaDesc),
            'body' => $bodyText,
            'headings' => array_unique($headings),
            'excerpt' => trim($excerpt),
        ];

        // Eğer link çıkarma istenmişse, linkleri çıkar
        if ($this->extractLinks) {
            $result['links'] = $this->extractPageLinks($doc, $baseUrl);
        }

        return $result;
    }

    /**
     * Sayfadaki tüm linkleri çıkarır
     */
    private function extractPageLinks(\DOMDocument $doc, string $baseUrl): array
    {
        $links = [];
        $seenUrls = [];
        $xpath = new \DOMXPath($doc);
        $anchorNodes = $xpath->query('//a[@href]');

        foreach ($anchorNodes as $anchor) {
            $href = $anchor->getAttribute('href');
            if (empty($href) || $href === '#') {
                continue;
            }

            // Relative URL'leri absolute URL'e çevir
            $absoluteUrl = $this->resolveUrl($href, $baseUrl);
            if (!$absoluteUrl) {
                continue;
            }

            // Duplicate kontrolü
            if (isset($seenUrls[$absoluteUrl])) {
                continue;
            }
            $seenUrls[$absoluteUrl] = true;

            // Link text'ini al
            $text = trim($anchor->textContent ?? '');
            if (empty($text)) {
                // Eğer text yoksa, img alt text'ini veya title attribute'unu dene
                $img = $xpath->query('.//img', $anchor)->item(0);
                if ($img) {
                    $text = $img->getAttribute('alt') ?: $img->getAttribute('title') ?: '';
                }
            }

            // Title attribute'unu al
            $title = $anchor->getAttribute('title') ?: null;

            $links[] = [
                'url' => $absoluteUrl,
                'text' => trim($text),
                'title' => $title ? trim($title) : null,
            ];
        }

        return $links;
    }

    /**
     * Relative URL'yi absolute URL'e çevirir
     */
    private function resolveUrl(string $url, string $baseUrl): ?string
    {
        if (empty($baseUrl)) {
            return $url;
        }

        // Zaten absolute URL ise
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        // Base URL'den scheme ve host'u al
        $parsedBase = parse_url($baseUrl);
        if (!$parsedBase) {
            return null;
        }

        $scheme = $parsedBase['scheme'] ?? 'http';
        $host = $parsedBase['host'] ?? '';
        $path = $parsedBase['path'] ?? '/';

        // Eğer URL / ile başlıyorsa, root-relative
        if (str_starts_with($url, '/')) {
            return $scheme . '://' . $host . $url;
        }

        // Eğer URL // ile başlıyorsa, protocol-relative
        if (str_starts_with($url, '//')) {
            return $scheme . ':' . $url;
        }

        // Relative URL - base path'e göre çöz
        $basePath = dirname($path);
        if ($basePath === '.') {
            $basePath = '/';
        }
        if (!str_ends_with($basePath, '/')) {
            $basePath .= '/';
        }

        return $scheme . '://' . $host . $basePath . $url;
    }
}