<?php

declare(strict_types=1);

namespace App\Nodes;

use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;
use App\Agents\Agent;
use App\Models\Agent as AgentModel;
use App\NeuronEvents\CompetitorCompletedEvent; // Girdi Event'i
use App\NeuronEvents\ContentIdeationCompletedEvent; // Çıktı Event'i (Bu dosyayı oluşturmanız gerekebilir)
use Illuminate\Support\Facades\Log;
use NeuronAI\Chat\Messages\UserMessage;

class ContentIdeationNode extends Node
{
    private array $agents = [];
    
    public function __construct(protected int $maxCrossAgents = 3)
    {
        Log::info('ContentIdeationNode constructor called', ['maxCrossAgents' => $this->maxCrossAgents]);
        try {
            $agents = AgentModel::where('node_class', get_class($this))->get();
            foreach($agents as $agent) {
                // Agent'ı oluştururken limiti ayarla
                $newAgent = new Agent($agent);
                // $newAgent->toolMaxTries(15); // Ideation agent'ı araç kullanmıyorsa buna gerek yok
                $this->agents[] = $newAgent;
            }
        } catch (\Exception $e) {
            Log::warning('No agents found for node class: ' . get_class($this), ['error' => $e->getMessage()]);
        }
    }

    /**
     * Implement the Node's logic
     * Bu node, CompetitorCompletedEvent ile tetiklenir
     */
    public function __invoke(CompetitorCompletedEvent $event, WorkflowState $state): ContentIdeationCompletedEvent
    {
        $research = $state->get('research');
        $summary = $state->get('analysis_summary');
        $trendData = $state->get('trend_data');
        $competitorData = $event->competitorData; // Önceki node'dan gelen veri
        
        Log::info('Content ideation started', [
            'research_id' => $research->id,
            'trend_data_available' => !empty($trendData),
            'competitor_data_available' => !empty($competitorData)
        ]);

        // Update research status
        $research->update(['status' => 'content_ideation']);
        
        // Ajanları kullanarak içerik fikirleri üret
        Log::info('Using agents for content ideation', ['agent_count' => count($this->agents)]);
        $ideationData = $this->performAgentContentIdeation(
            $research, 
            $summary, 
            $trendData, 
            $competitorData
        );
        
        // Save ideation data to research model
        $research->update(['content_ideas' => $ideationData]);
        $state->set('content_ideas', $ideationData);
        
        Log::info('Content ideation completed', ['research_id' => $research->id]);
        
        // Bir sonraki node'u tetikle
        return new ContentIdeationCompletedEvent($ideationData, 'Content ideation completed successfully');
    }

    /**
     * Perform content ideation using agents with Structured Output
     */
    private function performAgentContentIdeation($research, $summary, $trendData, $competitorData): array
    {
        $ideas = [];
        
        // Ajanın (AgentSeeder'daki 'steps') kullanması için ham veriyi hazırla
        // Ajanın talimatı: "Workflow state'ten trend_data ve competitor_data'yı al."
        // Biz bu verileri ona JSON olarak sunuyoruz.
        $taskData = [
            'research_request' => [
                'title' => $research->title,
                'content_type' => $research->content_type,
                'target_audience' => $summary?->target_audience ?? $research->target_audience,
                'tone' => $summary?->tone ?? $research->tone
            ],
            'analysis_summary' => $summary,
            'trend_data' => $trendData, // TrendResearchNode DTO'su
            'competitor_data' => $competitorData // CompetitorAnalysisNode DTO'su
        ];

        // Veri çok büyük olabileceğinden, ajana JSON olarak verelim.
        $taskDataJson = json_encode($taskData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to encode task data for ContentIdeationNode', ['error' => json_last_error_msg()]);
            // JSON hatası durumunda fallback'e git
            return $this->getFallbackDto();
        }
        
        foreach ($this->agents as $agent) {
            try {
                Log::info('Starting agent structured run for Content Ideation', [
                    'agent' => $agent->getModel()->name,
                    'task_data_size_kb' => round(strlen($taskDataJson) / 1024, 2)
                ]);
                
                // Ajanın 'steps' talimatı (Seeder'dan gelir) bu JSON verisini girdi olarak kullanır
                $result = $agent->structured(
                    new UserMessage($taskDataJson), // TALİMAT DEĞİL, HAM VERİ
                    \App\Dtos\ContentIdeationDto::class
                );
                
                Log::info('Agent structured run for Content Ideation completed', ['agent' => $agent->getModel()->name]);

                $ideas[] = [
                    'source' => $agent->getModel()->name,
                    'data' => $result,
                    'timestamp' => now()
                ];

            } catch (\Exception $e) {
                Log::error('Agent content ideation failed', [
                    'agent' => $agent->getModel()->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString() // Hatayı daha iyi anlamak için trace ekleyelim
                ]);
            }
        }
        
        // Eğer tüm agent denemeleri başarısızsa (veya hiç fikir üretilmediyse) fallback DTO üret
        if (empty($ideas)) {
            Log::warning('No content ideas from agents; falling back to minimal structured dto');
            return $this->getFallbackDto();
        }
        
        return $ideas;
    }

    /**
     * Ajanlar başarısız olursa boş bir DTO döndürür
     */
    private function getFallbackDto(): array
    {
        $dto = new \App\Dtos\ContentIdeationDto();
        $dto->content_ideas = [];
        $dto->content_angles = [];
        $dto->suggested_hashtags = [];
        $dto->format_recommendations = [];
        $dto->call_to_actions = [];
        $dto->visual_concepts = [];
        $dto->content_series = [];

        return [
            [
                'source' => 'fallback',
                'data' => $dto,
                'timestamp' => now(),
            ]
        ];
    }
}