<?php

declare(strict_types=1);

namespace App\NeuronEvents;

use NeuronAI\Workflow\Event;

class TrendCompletedEvent implements Event
{
    public function __construct(
        public array $trendData,
        public string $researchSummary
    ) {}
}
