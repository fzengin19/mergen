<?php

declare(strict_types=1);

namespace App\Tools;

use App\Observers\PageVisitObserver;
use GuzzleHttp\RequestOptions;
use Spatie\Browsershot\Browsershot;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;
use Illuminate\Support\Facades\Log;
use NeuronAI\Tools\PropertyType;

class VisitPageTool extends Tool
{
    public function __construct()
    {
        parent::__construct(
            'VisitPageTool',
            'Advanced web page visitor with JavaScript support. It visits a URL and returns a clean summary of its content.'
        );
    }

    protected function properties(): array
    {
        return [
            new ToolProperty(
                name: 'url',
                type: PropertyType::STRING,
                description: 'The URL of the page to visit.',
                required: true,
            ),
            new ToolProperty(
                name: 'extract_links',
                type: PropertyType::BOOLEAN,
                description: 'If true, extracts all links from the page. Default is false.',
                required: false,
            ),
            new ToolProperty(
                name: 'extract_media',
                type: PropertyType::BOOLEAN,
                description: 'If true, extracts all accessible media URLs (images, videos, posters, audio, OG/Twitter images) with absolute URLs.',
                required: false,
            ),
        ];
    }

    public function __invoke(string $url, bool $extract_links = false, bool $extract_media = false): string
    {
        // Sabit parametreler
        $enable_javascript = true;
        $timeout = 45; // JS render için timeout'u biraz artırmak iyi olabilir
        $wait_for_network_idle = true;
        $user_agent = $this->getRealisticUserAgent();

        Log::info('[VisitPageTool] Starting visit', compact('url', 'enable_javascript', 'timeout', 'extract_links', 'extract_media'));

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return json_encode(['error' => 'Invalid URL provided', 'url' => $url]);
        }

        // PageVisitObserver şu an (originalUrl, extractLinks, baseUrl) imzasına sahip.
        // Media desteğini PageVisitObserver tarafında manuel ekleyeceğiz; şimdilik extract_media
        // bayrağını baseUrl parametresi ile karıştırmayalım.
        $observer = new PageVisitObserver(null, $extract_links, $url);
        
        // Gerçekçi header'lar ekle
        $headers = [
            'User-Agent' => $user_agent,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'en-US,en;q=0.9,tr;q=0.8',
            'Accept-Encoding' => 'gzip, deflate, br',
            'DNT' => '1',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'Cache-Control' => 'max-age=0',
        ];
        
        $crawler = Crawler::create([
            RequestOptions::TIMEOUT => $timeout,
            RequestOptions::CONNECT_TIMEOUT => 15,
            RequestOptions::HEADERS => $headers,
        ]);

        try {
            $browsershot = Browsershot::url($url)
                ->timeout($timeout)
                ->setOption('waitUntil', $wait_for_network_idle ? 'networkidle0' : 'domcontentloaded')
                ->ignoreHttpsErrors()
                ->disableImages()
                ->userAgent($user_agent)
                ->setExtraHttpHeaders([
                    'Accept' => $headers['Accept'],
                    'Accept-Language' => $headers['Accept-Language'],
                    'Accept-Encoding' => $headers['Accept-Encoding'],
                    'DNT' => $headers['DNT'],
                    'Connection' => $headers['Connection'],
                    'Upgrade-Insecure-Requests' => $headers['Upgrade-Insecure-Requests'],
                    'Sec-Fetch-Dest' => $headers['Sec-Fetch-Dest'],
                    'Sec-Fetch-Mode' => $headers['Sec-Fetch-Mode'],
                    'Sec-Fetch-Site' => $headers['Sec-Fetch-Site'],
                    'Sec-Fetch-User' => $headers['Sec-Fetch-User'],
                    'Cache-Control' => $headers['Cache-Control'],
                ])
                ->setOption('args', [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-accelerated-2d-canvas',
                    '--no-first-run',
                    '--no-zygote',
                    '--disable-gpu',
                ]);
            $crawler->setBrowsershot($browsershot);
        } catch (\Exception $e) {
            Log::error('[VisitPageTool] Browsershot initialization failed', ['error' => $e->getMessage()]);
            return json_encode(['error' => 'JavaScript support (Browsershot) is not available.', 'message' => $e->getMessage()]);
        }

        $crawler
            ->setCrawlObserver($observer)
            ->setConcurrency(1)
            ->setMaximumDepth(0)
            ->setCrawlProfile(new class($url) extends CrawlProfile {
                public function __construct(private string $targetUrl) {}
                public function shouldCrawl(UriInterface $url): bool {
                    return (string) $url === $this->targetUrl;
                }
            });

        try {
            $crawler->startCrawling($url);
            $results = $observer->getResults();

            // --- YENİ VE EN ÖNEMLİ KISIM BURASI ---
            // Ajan için sonucu basitleştir ve temizle
            if (!empty($results['errors'])) {
                $errorMessage = "Crawling failed. Error: " . ($results['errors'][0]['message'] ?? 'Unknown error');
                Log::warning('[VisitPageTool] Crawling resulted in error', ['error' => $errorMessage]);
                return $errorMessage;
            }

            if (empty($results['page_data'])) {
                Log::warning('[VisitPageTool] Crawling succeeded but no page data was extracted.');
                return "Crawling succeeded but no content was extracted from the page.";
            }

            $pageData = $results['page_data'];
            $cleanContent = "Page Title: " . ($pageData['title'] ?? 'N/A') . "\n\n";
            $cleanContent .= "Key Headings: " . implode(', ', $pageData['headings'] ?? []) . "\n\n";
            $cleanContent .= "Page Content Summary:\n---\n" . ($pageData['body'] ?? 'No body content found.');

            // Eğer link çıkarma istenmişse, linkleri ekle
            if ($extract_links && !empty($pageData['links'])) {
                $cleanContent .= "\n\nPage Links:\n---\n";
                foreach ($pageData['links'] as $index => $link) {
                    $num = $index + 1;
                    $linkText = !empty($link['text']) ? $link['text'] : '(no text)';
                    $linkTitle = !empty($link['title']) ? " [{$link['title']}]" : '';
                    $cleanContent .= "{$num}. {$linkText}{$linkTitle}\n   URL: {$link['url']}\n\n";
                }
            }

            // Eğer medya çıkarma istenmişse, medyaları ekle
            if ($extract_media && !empty($pageData['media'])) {
                $cleanContent .= "\n\nPage Media:\n---\n";
                foreach ($pageData['media'] as $index => $media) {
                    $num = $index + 1;
                    $type = $media['type'] ?? 'media';
                    $urlValue = $media['url'] ?? '';
                    if ($urlValue === '') {
                        continue;
                    }

                    $labelParts = [];
                    $labelParts[] = "[{$type}]";

                    if (!empty($media['tag'])) {
                        $labelParts[] = "tag={$media['tag']}";
                    }
                    if (!empty($media['attr'])) {
                        $labelParts[] = "attr={$media['attr']}";
                    }
                    if (!empty($media['alt'])) {
                        $labelParts[] = "alt=\"{$media['alt']}\"";
                    }
                    if (!empty($media['title'])) {
                        $labelParts[] = "title=\"{$media['title']}\"";
                    }

                    $label = implode(' ', $labelParts);
                    $cleanContent .= "{$num}. {$label}\n   URL: {$urlValue}\n\n";
                }
            }

            Log::info('[VisitPageTool] Successfully returned clean content.', ['url' => $url, 'extract_links' => $extract_links]);
            
            // Ajana karmaşık JSON yerine bu temiz metni döndür
            return $cleanContent;
            // --- DEĞİŞİKLİK SONU ---

        } catch (\Exception $e) {
            Log::error('[VisitPageTool] Critical crawling exception', ['error' => $e->getMessage()]);
            return json_encode(['error' => 'Crawling failed critically', 'url' => $url, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Gerçekçi bir tarayıcı User-Agent string'i döndürür.
     * Bot tespitinden kaçınmak için çeşitli güncel tarayıcı UA'ları içerir.
     */
    private function getRealisticUserAgent(): string
    {
        // Güncel ve popüler Chrome User-Agent'ları
        $chromeAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];

        // Firefox User-Agent'ları
        $firefoxAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:121.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (X11; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0',
        ];

        // Safari User-Agent'ları
        $safariAgents = [
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1',
        ];

        // Edge User-Agent'ları
        $edgeAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
        ];

        $allAgents = array_merge($chromeAgents, $firefoxAgents, $safariAgents, $edgeAgents);
        
        // Rastgele bir agent seç ama Chrome'a öncelik ver (en popüler olduğu için)
        $useChrome = rand(1, 100) <= 60; // %60 ihtimalle Chrome
        $useFirefox = rand(1, 100) <= 25; // %25 ihtimalle Firefox
        $useSafari = rand(1, 100) <= 10; // %10 ihtimalle Safari
        // Kalan %5 Edge

        if ($useChrome) {
            return $chromeAgents[array_rand($chromeAgents)];
        } elseif ($useFirefox) {
            return $firefoxAgents[array_rand($firefoxAgents)];
        } elseif ($useSafari) {
            return $safariAgents[array_rand($safariAgents)];
        } else {
            return $edgeAgents[array_rand($edgeAgents)];
        }
    }
}
