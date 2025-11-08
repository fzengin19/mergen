<?php

declare(strict_types=1);

namespace App\NeuronEvents;

use NeuronAI\Workflow\Event;

class CompetitorCompletedEvent implements Event
{
    public function __construct(
        public array $competitorData,
        public string $analysisSummary
    ) {}
}
