<?php

declare(strict_types=1);

namespace App\Dtos;

use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
use NeuronAI\StructuredOutput\Validation\Rules\Count;

class StartResearchSummaryDto
{
    #[SchemaProperty(description: 'İçerik konusu (özet başlık).', required: true)]
    #[NotBlank]
    public string $topic;

    #[SchemaProperty(description: 'Niş/alan (örn. bal, arıcılık, KOBİ).', required: false)]
    public ?string $niche = null;

    #[SchemaProperty(description: 'Araştırmanın amacı (içerik üret, büyüme, satış).', required: true)]
    #[NotBlank]
    public string $intent;

    #[SchemaProperty(description: 'Hedef kitle açıklaması.', required: false)]
    public ?string $target_audience = null;

    #[SchemaProperty(description: 'İçerik tipi (reel, post, story, carousel).', required: true)]
    #[NotBlank]
    public string $content_type;

    #[SchemaProperty(description: 'İçerik tonu (samimi, resmi, esprili vb.).', required: false)]
    public ?string $tone = null;

    #[SchemaProperty(description: 'TR anahtar kelimeler listesi.', required: false)]
    #[Count(min: 0, max: 20)]
    public array $keywords = [];

    #[SchemaProperty(description: 'Eşanlamlılar / alternatif terimler.', required: false)]
    #[Count(min: 0, max: 20)]
    public array $synonyms = [];
}


