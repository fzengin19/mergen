<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Research;
use App\Workflows\InstagramContentResearchWorkflow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunInstagramContentResearchWorkflow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 1800; // 30 dk

    public function __construct(private int $researchId)
    {
    }

    public function handle(): void
    {
        $research = Research::query()->find($this->researchId);

        if (!$research) {
            Log::warning('RunInstagramContentResearchWorkflow: Research not found', ['research_id' => $this->researchId]);
            return;
        }

        try {
            $research->update(['status' => 'queued']);

            Log::info('RunInstagramContentResearchWorkflow: starting', [
                'research_id' => $research->id,
            ]);

            $workflow = new InstagramContentResearchWorkflow($research);
            $handler = $workflow->start();
            $state = $handler->getResult();

            Log::info('RunInstagramContentResearchWorkflow: finished', [
                'research_id' => $research->id,
                'status' => $research->status,
            ]);
        } catch (\Throwable $e) {
            Log::error('RunInstagramContentResearchWorkflow: failed', [
                'research_id' => $this->researchId,
                'message' => $e->getMessage(),
            ]);

            $research?->update(['status' => 'failed']);

            // Kuyruğu durdurmadan hatayı sakince sonlandıralım
        }
    }
}


