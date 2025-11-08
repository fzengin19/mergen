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
use App\Dtos\StartResearchSummaryDto;
use App\NeuronEvents\StartResearchEvent;
use Illuminate\Support\Facades\Log;

class AnalyzeResearchNode extends Node
{
    private array $agents = [];
    public function __construct(protected int $maxCrossAgents = 3)
    {
        Log::info('AnalyzeResearchNode constructor called', ['maxCrossAgents' => $this->maxCrossAgents]);
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
    public function __invoke(StartEvent $event, WorkflowState $state): StartResearchEvent
    {
        $research = $state->get('research');
        Log::info('Analyzing research started', [
            'research_id' => $research->id,
            'title' => $research->title,
            'additional_info' => $research->additional_info
        ]);
        
        $summary = null;
        if (!empty($this->agents)) {
            Log::info('Using agents for request analysis', ['agent_count' => count($this->agents)]);
            foreach ($this->agents as $agent) {
                try {
                    $prompt = sprintf(
                        "Aşağıdaki bilgilerden yola çıkarak Türkçe bir araştırma özeti oluştur: başlık='%s', içerik_tipi='%s', hedef_kitle='%s', ton='%s', ek_bilgi='%s'. Çıktıda topic, niche (varsa), intent, target_audience, content_type, tone, keywords[3-10], synonyms[0-5] üret.",
                        $research->title,
                        $research->content_type,
                        $research->target_audience,
                        $research->tone,
                        $research->additional_info
                    );
                    /** @var StartResearchSummaryDto $dto */
                    $dto = $agent->structured(
                        new UserMessage($prompt),
                        StartResearchSummaryDto::class
                    );
                    $summary = $dto;
                    break;
                } catch (\Throwable $e) {
                    Log::warning('Agent request analysis failed', ['error' => $e->getMessage()]);
                }
            }
        }

        if (!$summary) {
            Log::info('Falling back to basic request analysis');
            $dto = new StartResearchSummaryDto();
            $dto->topic = $research->title;
            $dto->niche = null;
            $dto->intent = 'içerik üretimi';
            $dto->target_audience = $research->target_audience;
            $dto->content_type = $research->content_type;
            $dto->tone = $research->tone;
            $dto->keywords = [];
            $dto->synonyms = [];
            $summary = $dto;
        }

        // Persist & State
        $research->update(['analysis_summary' => $summary]);
        $state->set('analysis_summary', $summary);
        
        Log::info('Research analysis completed', ['research_id' => $research->id]);
        return new StartResearchEvent($summary);
    }
}
