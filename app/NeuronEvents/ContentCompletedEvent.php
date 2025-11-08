<?php

declare(strict_types=1);

namespace App\NeuronEvents;

use NeuronAI\Workflow\Event;

class ContentCompletedEvent implements Event
{
    public function __construct(
        public array $generatedContent,
        public string $generationSummary
    ) {}
}
