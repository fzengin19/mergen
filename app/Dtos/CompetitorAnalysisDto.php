<?php

declare(strict_types=1);

namespace App\Dtos;

use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
use NeuronAI\StructuredOutput\Validation\Rules\Count;

class CompetitorAnalysisDto
{
    #[SchemaProperty(description: 'List of top competitors in the niche', required: true)]
    #[NotBlank]
    #[Count(min: 3, max: 10)]
    public array $competitors;

    #[SchemaProperty(description: 'Analysis of competitor content strategies', required: true)]
    #[NotBlank]
    public array $content_strategies;

    #[SchemaProperty(description: 'Competitor engagement rates and tactics', required: true)]
    #[NotBlank]
    public array $engagement_tactics;

    #[SchemaProperty(description: 'Hashtag patterns used by competitors', required: false)]
    public array $hashtag_patterns;

    #[SchemaProperty(description: 'Posting frequency and timing analysis', required: false)]
    public array $posting_patterns;

    #[SchemaProperty(description: 'Content performance gaps and opportunities', required: false)]
    public array $opportunity_gaps;

    #[SchemaProperty(description: 'Unique selling propositions compared to competitors', required: false)]
    public array $unique_angles;
}
