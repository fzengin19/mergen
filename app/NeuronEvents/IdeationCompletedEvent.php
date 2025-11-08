<?php

declare(strict_types=1);

namespace App\NeuronEvents;

use NeuronAI\Workflow\Event;

class IdeationCompletedEvent implements Event
{
    public function __construct(
        public array $contentIdeas,
        public string $ideationSummary
    ) {}
}
