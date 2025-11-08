<?php

declare(strict_types=1);

namespace App\Dtos;

use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
use NeuronAI\StructuredOutput\Validation\Rules\Count;
use NeuronAI\StructuredOutput\Validation\Rules\WordsCount;

class ContentGenerationDto
{
    #[SchemaProperty(description: 'Generated Instagram post captions', required: true)]
    #[NotBlank]
    #[Count(min: 3, max: 10)]
    public array $captions;

    #[SchemaProperty(description: 'Complete hashtag sets for each caption', required: true)]
    #[NotBlank]
    public array $hashtags;

    #[SchemaProperty(description: 'Content format for each post (post, reel, story, carousel)', required: true)]
    #[NotBlank]
    public array $content_formats;

    #[SchemaProperty(description: 'Visual descriptions for image/video content', required: false)]
    public array $visual_descriptions;

    #[SchemaProperty(description: 'Call-to-action phrases for each post', required: false)]
    public array $call_to_actions;

    #[SchemaProperty(description: 'Engagement questions to boost interaction', required: false)]
    public array $engagement_questions;

    #[SchemaProperty(description: 'Story highlights or key talking points', required: false)]
    public array $story_points;
}
