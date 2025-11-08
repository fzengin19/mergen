<?php

declare(strict_types=1);

namespace App\Nodes;

use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;
use App\Agents\Agent;
use App\Models\Agent as AgentModel;
use App\NeuronEvents\ContentIdeationCompletedEvent; // DİNLENECEK EVENT DÜZELTİLDİ
use App\NeuronEvents\ContentCompletedEvent; // Fırlatılacak event (bu zaten doğruydu)
use Illuminate\Support\Facades\Log;
use NeuronAI\Chat\Messages\UserMessage;

class ContentGenerationNode extends Node
{
    private array $agents = [];
    
    public function __construct(protected int $maxCrossAgents = 3)
    {
        Log::info('ContentGenerationNode constructor called', ['maxCrossAgents' => $this->maxCrossAgents]);
        try {
            $agents = AgentModel::where('node_class', get_class($this))->get();
            foreach($agents as $agent) {
                $newAgent = new Agent($agent);
                // $newAgent->toolMaxTries(15); // Generation agent'ı araç kullanmıyorsa buna gerek yok
                $this->agents[] = $newAgent;
            }
        } catch (\Exception $e) {
            Log::warning('No agents found for node class: ' . get_class($this), ['error' => $e->getMessage()]);
        }
    }

    /**
     * Implement the Node's logic
     * DİKKAT: Event adı ContentIdeationCompletedEvent olarak güncellendi.
     */
    public function __invoke(ContentIdeationCompletedEvent $event, WorkflowState $state): ContentCompletedEvent
    {
        $research = $state->get('research');
        $summary = $state->get('analysis_summary');
        
        // Önceki node'lardan gelen DTO verilerini al
        $contentIdeas = $event->contentIdeas;
        $trendData = $state->get('trend_data');
        $competitorData = $state->get('competitor_data');
        
        Log::info('Content generation started', [
            'research_id' => $research->id,
            'content_ideas_available' => !empty($contentIdeas)
        ]);

        // Fikir verisi boşsa veya fallback DTO ise, fallback ile devam et
        if (empty($contentIdeas) || ($contentIdeas[0]['source'] ?? '') === 'fallback') {
            Log::warning('No content ideas available, falling back to minimal content DTO');
            $generatedContent = $this->getFallbackDto();
        } else {
            // Update research status
            $research->update(['status' => 'content_generation']);
            
            Log::info('Using agents for content generation', ['agent_count' => count($this->agents)]);
            
            // AJAN ÇAĞRISI DÜZELTİLDİ:
            // Agent'ı kullanarak gerçek içerik üretimi yap
            $generatedContent = $this->performAgentContentGeneration(
                $research,
                $summary,
                $trendData,
                $competitorData,
                $contentIdeas
            );
        }
        
        // Save generated content to research model
        $research->update(['generated_content' => $generatedContent]);
        $state->set('generated_content', $generatedContent);
        
        Log::info('Content generation completed', ['research_id' => $research->id]);
        return new ContentCompletedEvent($generatedContent, 'Content generation completed successfully');
    }

    /**
     * Perform content generation using agents with Structured Output
     */
    private function performAgentContentGeneration($research, $summary, $trendData, $competitorData, $contentIdeas): array
    {
        $content = [];
        
        // Ajanın (AgentSeeder'daki 'steps') kullanması için ham veriyi hazırla
        // Ajanın talimatı: "Workflow state'ten ... al."
        // Biz bu verileri ona JSON olarak sunuyoruz.
        $taskData = [
            'research_request' => [
                'title' => $research->title,
                'content_type' => $research->content_type,
                'target_audience' => $summary?->target_audience ?? $research->target_audience,
                'tone' => $summary?->tone ?? $research->tone
            ],
            'trend_data' => $trendData,
            'competitor_data' => $competitorData,
            'content_ideas' => $contentIdeas // Bir önceki node'da üretilen fikirler
        ];

        $taskDataJson = json_encode($taskData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to encode task data for ContentGenerationNode', ['error' => json_last_error_msg()]);
            return $this->getFallbackDto();
        }

        foreach ($this->agents as $agent) {
            try {
                Log::info('Starting agent structured run for Content Generation', [
                    'agent' => $agent->getModel()->name,
                    'task_data_size_kb' => round(strlen($taskDataJson) / 1024, 2)
                ]);

                // Ajanın 'steps' talimatı (Seeder'dan gelir) bu JSON verisini girdi olarak kullanır
                $result = $agent->structured(
                    new UserMessage($taskDataJson), // TALİMAT DEĞİL, HAM VERİ
                    \App\Dtos\ContentGenerationDto::class
                );
                
                Log::info('Agent structured run for Content Generation completed', ['agent' => $agent->getModel()->name]);

                $content[] = [
                    'source' => $agent->getModel()->name,
                    'data' => $result,
                    'timestamp' => now()
                ];

            } catch (\Exception $e) {
                Log::error('Agent content generation failed', [
                    'agent' => $agent->getModel()->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString() // Hatayı daha iyi anlamak için trace ekleyelim
                ]);
            }
        }
        
        if (empty($content)) {
            Log::warning('No content generated from agents; falling back to minimal structured dto');
            return $this->getFallbackDto();
        }
        
        return $content;
    }

    /**
     * Ajanlar başarısız olursa boş bir DTO döndürür
     */
    private function getFallbackDto(): array
    {
        $dto = new \App\Dtos\ContentGenerationDto();
        $dto->captions = [];
        $dto->hashtags = [];
        $dto->content_formats = [];
        $dto->visual_descriptions = [];
        $dto->call_to_actions = [];
        $dto->engagement_questions = [];
        $dto->story_points = [];

        return [
            [
                'source' => 'fallback',
                'data' => $dto,
                'timestamp' => now(),
            ]
        ];
    }
}