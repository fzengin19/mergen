<?php

declare(strict_types=1);

namespace App\Nodes;

use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\StartEvent;
use NeuronAI\Workflow\StopEvent;
use NeuronAI\Workflow\WorkflowState;
use App\Agents\Agent;
use App\Models\Agent as AgentModel;
use App\NeuronEvents\TrendCompletedEvent;
use App\NeuronEvents\CompetitorCompletedEvent;
use Illuminate\Support\Facades\Log;
use NeuronAI\Chat\Messages\UserMessage; // Hata düzeltmesi için eklendi

class CompetitorAnalysisNode extends Node
{
    private array $agents = [];
    
    public function __construct(protected int $maxCrossAgents = 3)
    {
        Log::info('CompetitorAnalysisNode constructor called', ['maxCrossAgents' => $this->maxCrossAgents]);
        try {
            $agents = AgentModel::where('node_class', get_class($this))->get();
            foreach($agents as $agent) {
                $this->agents[] = new Agent($agent);
            }
        } catch (\Exception $e) {
            Log::warning('No agents found for node class: ' . get_class($this), ['error' => $e->getMessage()]);
        }
    }

    /**
     * Implement the Node's logic
     */
    public function __invoke(TrendCompletedEvent $event, WorkflowState $state): CompetitorCompletedEvent
    {
        $research = $state->get('research');
        $trendData = $event->trendData;
        
        Log::info('Competitor analysis started', [
            'research_id' => $research->id,
            'title' => $research->title,
            'trend_data_available' => !empty($trendData)
        ]);

        // Update research status
        $research->update(['status' => 'competitor_analysis']);
        
        // 'else' bloğu kaldırıldı. Sadece ajan yolu kullanılıyor.
        Log::info('Using agents for competitor analysis', ['agent_count' => count($this->agents)]);
        $competitorData = $this->performAgentCompetitorAnalysis($research, $trendData);
        
        // Save competitor data to research model
        $research->update(['competitor_data' => $competitorData]);
        $state->set('competitor_data', $competitorData);
        
        Log::info('Competitor analysis completed', ['research_id' => $research->id]);
        return new CompetitorCompletedEvent($competitorData, 'Competitor analysis completed successfully');
    }

    /**
     * Perform competitor analysis using agents with Structured Output
     */
    private function performAgentCompetitorAnalysis($research, $trendData): array
    {
        $competitors = [];
        
        // DÜZELTME: Ajanın talimatlarını ezmek yerine,
        // ona sadece işlemesi gereken GÖREV VERİSİNİ sağlıyoruz.
        $taskData = sprintf(
            "Araştırma Konusu/Nişi: %s (%s). Hedef Kitle: %s. Ton: %s.",
            $research->title,
            $research->content_type,
            $research->target_audience,
            $research->tone
        );
        
        foreach ($this->agents as $agent) {
            try {
                Log::info('Starting agent structured run for Competitor Analysis', [
                    'agent' => $agent->getModel()->name,
                    'task' => $taskData
                ]);
                
                // DÜZELTME: 
                // 1. 'string' yerine 'new UserMessage' objesi gönderildi.
                // 2. Mesaj, ajanın 'steps' talimatlarını ezmeyen ham GÖREV verisini içeriyor.
                $result = $agent->structured(
                    new UserMessage($taskData),
                    \App\Dtos\CompetitorAnalysisDto::class
                );
                
                Log::info('Agent structured run for Competitor Analysis completed', ['agent' => $agent->getModel()->name]);

                $competitors[] = [
                    'source' => $agent->getModel()->name,
                    'data' => $result,
                    'timestamp' => now()
                ];
            } catch (\Exception $e) {
                Log::error('Agent competitor analysis failed', [
                    'agent' => $agent->getModel()->name,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Eğer tüm agent denemeleri başarısızsa güvenli minimal DTO üret
        if (empty($competitors)) {
            Log::warning('No competitor data from agents; falling back to minimal structured dto');
            $dto = new \App\Dtos\CompetitorAnalysisDto();
            $dto->competitors = [];
            $dto->content_strategies = [];
            $dto->engagement_tactics = [];
            $dto->hashtag_patterns = [];
            $dto->posting_patterns = [];
            $dto->opportunity_gaps = [];
            $dto->unique_angles = [];
            $competitors[] = [
                'source' => 'fallback',
                'data' => $dto,
                'timestamp' => now(),
            ];
        }
        
        return $competitors;
    }

    // 'performBasicCompetitorAnalysis' ve tüm yardımcı metodları ('analyze...', 'extract...', 'identify...') kaldırıldı.
    // Artık tüm mantık ajan tarafından yönetiliyor.
}