<?php

declare(strict_types=1);

namespace App\Dtos;

use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;

class TrendResearchDto
{
    #[SchemaProperty(description: 'List of current Instagram trends related to the research topic', required: true)]
    #[NotBlank]
    public array $trends;

    #[SchemaProperty(description: 'List of popular hashtags for the content type', required: true)]
    #[NotBlank]
    public array $hashtags;

    #[SchemaProperty(description: 'Optimal posting times based on audience analysis', required: false)]
    public array $optimal_times;

    #[SchemaProperty(description: 'Content format recommendations (reel, post, story, carousel)', required: false)]
    public array $content_formats;

    #[SchemaProperty(description: 'Target audience insights and preferences', required: false)]
    public array $audience_insights;

    #[SchemaProperty(description: 'Engagement strategies specific to the content type', required: false)]
    public array $engagement_strategies;
}
