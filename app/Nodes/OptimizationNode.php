<?php

declare(strict_types=1);

namespace App\Nodes;

use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\StartEvent;
use NeuronAI\Workflow\StopEvent;
use NeuronAI\Workflow\WorkflowState;
use App\Agents\Agent;
use App\Models\Agent as AgentModel;
use App\NeuronEvents\ContentCompletedEvent;
use Illuminate\Support\Facades\Log;
use NeuronAI\Chat\Messages\UserMessage;

class OptimizationNode extends Node
{
    private array $agents = [];
    
    public function __construct(protected int $maxCrossAgents = 3)
    {
        Log::info('OptimizationNode constructor called', ['maxCrossAgents' => $this->maxCrossAgents]);
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
    public function __invoke(ContentCompletedEvent $event, WorkflowState $state): StopEvent
    {
        $research = $state->get('research');
        $generatedContent = $event->generatedContent;
        $trendData = $state->get('trend_data');
        $competitorData = $state->get('competitor_data');
        
        Log::info('Optimization started', [
            'research_id' => $research->id,
            'generated_content_available' => !empty($generatedContent)
        ]);
        
        // Update research status
        $research->update(['status' => 'optimization']);
        
        Log::info('Using agents for optimization', ['agent_count' => count($this->agents)]);
        $optimizedContent = $this->performAgentOptimization(
            $research,
            $generatedContent,
            $trendData,
            $competitorData
        );
        
        // Save optimized content to research model
        $research->update([
            'optimized_content' => $optimizedContent,
            'status' => 'completed'
        ]);
        $state->set('optimized_content', $optimizedContent);
        
        Log::info('Optimization completed', ['research_id' => $research->id]);
        return new StopEvent();
    }

    /**
     * Perform optimization using agents with Structured Output
     */
    private function performAgentOptimization($research, $generatedContent, $trendData, $competitorData): array
    {
        $optimized = [];
        
        // Ajanın (AgentSeeder'daki 'steps') kullanması için ham veriyi hazırla
        $taskData = [
            'research_request' => [
                'title' => $research->title,
                'content_type' => $research->content_type,
                'target_audience' => $research->target_audience,
                'tone' => $research->tone
            ],
            'generated_content' => $generatedContent,
            'trend_data' => $trendData,
            'competitor_data' => $competitorData
        ];

        $taskDataJson = json_encode($taskData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to encode task data for OptimizationNode', ['error' => json_last_error_msg()]);
            return [];
        }

        foreach ($this->agents as $agent) {
            try {
                Log::info('Starting agent structured run for Optimization', [
                    'agent' => $agent->getModel()->name,
                    'task_data_size_kb' => round(strlen($taskDataJson) / 1024, 2)
                ]);

                // Ajanın 'steps' talimatı (Seeder'dan gelir) bu JSON verisini girdi olarak kullanır
                $result = $agent->structured(
                    new UserMessage($taskDataJson),
                    \App\Dtos\ContentGenerationDto::class
                );
                
                Log::info('Agent structured run for Optimization completed', ['agent' => $agent->getModel()->name]);

                $optimized[] = [
                    'source' => $agent->getModel()->name,
                    'data' => $result,
                    'timestamp' => now()
                ];

            } catch (\Exception $e) {
                Log::error('Agent optimization failed', [
                    'agent' => $agent->getModel()->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        return $optimized;
    }
}
