<?php

declare(strict_types=1);

namespace App\Dtos;

use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
use NeuronAI\StructuredOutput\Validation\Rules\Count;

class ContentIdeationDto
{
    #[SchemaProperty(description: 'List of creative content ideas based on research', required: true)]
    #[NotBlank]
    #[Count(min: 5, max: 15)]
    public array $content_ideas;

    #[SchemaProperty(description: 'Content angles and storytelling approaches', required: true)]
    #[NotBlank]
    public array $content_angles;

    #[SchemaProperty(description: 'Suggested hashtags for each content idea', required: true)]
    #[NotBlank]
    public array $suggested_hashtags;

    #[SchemaProperty(description: 'Content format recommendations for each idea', required: false)]
    public array $format_recommendations;

    #[SchemaProperty(description: 'Call-to-action suggestions', required: false)]
    public array $call_to_actions;

    #[SchemaProperty(description: 'Visual content suggestions and concepts', required: false)]
    public array $visual_concepts;

    #[SchemaProperty(description: 'Content series or multi-part ideas', required: false)]
    public array $content_series;
}
