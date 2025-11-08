<?php

declare(strict_types=1);

namespace App\Tools;

use GuzzleHttp\Client;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
use Illuminate\Support\Facades\Log;

class GoogleSearchTool extends Tool
{
    public function __construct()
    {
        parent::__construct(
            'GoogleSearchTool',
            'Search the web using Google Custom Search API and return structured results.'
        );
    }

    protected function properties(): array
    {
        return [
            new ToolProperty(
                name: 'query',
                type: PropertyType::STRING,
                description: 'Search query to look up on Google.',
                required: true,
            ),
        ];
    }

    public function __invoke(string $query): string
    {
        $client = new Client([
            'base_uri' => 'https://www.googleapis.com/customsearch/v1',
            'timeout' => 10,
        ]);

        try {
            $locale = config('services.instagram_research');
            $response = $client->get('', [
                'query' => array_filter([
                    'key' => config('services.google.api_key'),
                    'cx' => config('services.google.search_engine_id'),
                    'q' => $query,
                    // TR odaklı arama sinyalleri (varsa uygula)
                    'hl' => $locale['lang'] ?? null,
                    'gl' => $locale['gl'] ?? null,
                    'lr' => $locale['lr'] ?? null,
                ]),
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            $data = json_decode($body, true);
            $results = [];

            foreach (array_slice($data['items'] ?? [], 0, 5) as $item) {
                $results[] = [
                    'title' => $item['title'] ?? '(no title)',
                    'link' => $item['link'] ?? null,
                    'snippet' => $item['snippet'] ?? null,
                    'source' => 'Google',
                    'relevance_score' => 1.0,
                ];
            }

            // Sonuçları okunabilir formatta logla
            $resultCount = count($results);
            $logMessage = "[GoogleSearchTool] Search completed - Query: \"{$query}\", Results: {$resultCount}\n";
            foreach ($results as $index => $result) {
                $num = $index + 1;
                $title = $result['title'];
                $link = $result['link'] ?? 'N/A';
                $logMessage .= "{$num}. Title: {$title} | Link: {$link}\n";
            }
            Log::info(trim($logMessage));

            $jsonResult = json_encode([
                'query' => $query,
                'results' => $results,
            ], JSON_PRETTY_PRINT);

            return $jsonResult;

        } catch (\Throwable $e) {
            Log::error("[GoogleSearchTool] Exception occurred", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return json_encode([
                'error' => 'Google search failed',
                'message' => $e->getMessage(),
            ]);
        }
    }
}