<?php

declare(strict_types=1);

namespace App\Nodes;

use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\StartEvent;
use NeuronAI\Workflow\StopEvent;
use NeuronAI\Workflow\WorkflowState;
use NeuronAI\Chat\Messages\UserMessage;
use App\Agents\Agent;
use App\Models\Agent as AgentModel;
use App\NeuronEvents\TrendCompletedEvent;
use App\NeuronEvents\StartResearchEvent;
// Artık 'performDeepTrendResearch' kullanılmadığı için GoogleSearchTool ve VisitPageTool'a burada gerek kalmadı.
// Onları Agent sınıfınız zaten yönetiyor.
use Illuminate\Support\Facades\Log;

class TrendResearchNode extends Node
{
    private array $agents = [];
    
    public function __construct(protected int $maxCrossAgents = 3)
    {
        Log::info('TrendResearchNode constructor called', ['maxCrossAgents' => $this->maxCrossAgents]);
        try {
            // Seeder'da bu node için tanımlanan ajanları yükle
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
    public function __invoke(StartResearchEvent $event, WorkflowState $state): TrendCompletedEvent
    {
        $research = $state->get('research');
        $summary = $state->get('analysis_summary') ?? $event->summary ?? null;
        Log::info('Trend research started', [
            'research_id' => $research->id,
            'title' => $research->title,
            'content_type' => $research->content_type
        ]);

        // Update research status
        $research->update(['status' => 'trend_research']);
        
        // TALEP ETTİĞİNİZ GİBİ: 'else' (deep research) bloğu kaldırıldı.
        // Artık sadece ajan yolunu kullanıyoruz.
        // Ajanın yüklenememesi durumunda hata verecektir, bu beklenen bir davranıştır.
        Log::info('Using agents for trend research', ['agent_count' => count($this->agents)]);
        $trendData = $this->performAgentTrendResearch($research, $summary);
        
        // Save trend data to research model
        $research->update(['trend_data' => $trendData]);
        $state->set('trend_data', $trendData);
        
        Log::info('Trend research completed', ['research_id' => $research->id]);
        return new TrendCompletedEvent($trendData, 'Trend research completed successfully');
    }

    /**
     * Perform trend research using agents with Structured Output
     */
    private function performAgentTrendResearch($research, $summary = null): array
    {
        $trends = [];
        
        // Build prompt with summary information if available
        $kwStr = '';
        if ($summary && !empty($summary->keywords)) {
            $kwStr = ' Anahtar kelimeler: ' . implode(', ', array_slice($summary->keywords, 0, 5));
        }
        $contentType = $summary?->content_type ?? $research->content_type;
        $tone = $summary?->tone ?? $research->tone;
        $targetAudience = $summary?->target_audience ?? $research->target_audience;
        
        foreach ($this->agents as $agent) {
            try {
                // DÜZELTME: Ajanın 'steps' talimatlarını ezmek yerine,
                // ona sadece işlemesi gereken GÖREV VERİSİNİ sağlıyoruz.
                $taskData = sprintf(
                    "Araştırma Konusu: %s (%s). Hedef Kitle: %s. Ton: %s.%s",
                    $research->title,
                    $contentType,
                    $targetAudience,
                    $tone,
                    $kwStr
                );
                
                Log::info('Starting agent structured run', [
                    'agent' => $agent->getModel()->name,
                    'task' => $taskData
                ]);
                
                // Ajan, bu $taskData'yı alacak ve AgentSeeder'da tanımlanan
                // KENDİ 'steps' (1. Araştır, 2. Ziyaret et, 3. Analiz et...) adımlarını uygulayacak.
                $result = $agent->structured(
                    new UserMessage($taskData),
                    \App\Dtos\TrendResearchDto::class
                );
                
                Log::info('Agent structured run completed', ['agent' => $agent->getModel()->name]);

                $trends[] = [
                    'source' => $agent->getModel()->name,
                    'data' => $result,
                    'timestamp' => now()
                ];
            } catch (\Exception $e) {
                Log::error('Agent trend research failed', [
                    'agent' => $agent->getModel()->name,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $trends;
    }

}