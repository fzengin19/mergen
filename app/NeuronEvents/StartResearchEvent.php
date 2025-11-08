<?php

declare(strict_types=1);

namespace App\NeuronEvents;

use App\Dtos\StartResearchSummaryDto;
use NeuronAI\Workflow\Event;

class StartResearchEvent implements Event
{
    public function __construct(
        public StartResearchSummaryDto $summary
    ) {}
}


