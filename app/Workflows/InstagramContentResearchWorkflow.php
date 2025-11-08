<?php

declare(strict_types=1);

namespace App\Workflows;

use NeuronAI\Workflow\Workflow;
use NeuronAI\Workflow\WorkflowState;
use NeuronAI\Workflow\WorkflowException;
use App\Models\Research;
use App\Nodes\TrendResearchNode;
use App\Nodes\CompetitorAnalysisNode;
use App\Nodes\ContentIdeationNode;
use App\Nodes\ContentGenerationNode;
use App\Nodes\OptimizationNode;
use App\Nodes\AnalyzeResearchNode;
use Illuminate\Support\Facades\Log;

class InstagramContentResearchWorkflow extends Workflow
{
    protected Research $research;

    public function __construct(Research $research)
    {
        Log::info('InstagramContentResearchWorkflow started', ['research_id' => $research->id]);
        parent::__construct(new WorkflowState(['research' => $research]));
        $this->research = $research;
    }


    /**
     * Start workflow manually
     */
    public function start(bool $resume = false, mixed $externalFeedback = null): \NeuronAI\Workflow\WorkflowHandler
    {
        Log::info('Starting InstagramContentResearchWorkflow execution');
        return parent::start($resume, $externalFeedback);
    }

    /**
     * Define the workflow nodes in sequence
     */
    protected function nodes(): array
    {
        return [
            new AnalyzeResearchNode(),
            new TrendResearchNode(),
            new CompetitorAnalysisNode(),
            new ContentIdeationNode(),
            new ContentGenerationNode(),
            new OptimizationNode(),
        ];
    }


}
