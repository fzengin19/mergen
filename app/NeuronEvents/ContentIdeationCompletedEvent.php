<?php

declare(strict_types=1);

namespace App\NeuronEvents;

use NeuronAI\Workflow\Event;

/**
 * Bu event, ContentIdeationNode'un içerik fikirlerini
 * başarıyla ürettiğini ve bir sonraki adıma (Generation)
 * geçmeye hazır olduğunu bildirir.
 */
class ContentIdeationCompletedEvent implements Event
{
    /**
     * @param array $contentIdeas Ajan(lar) tarafından üretilen ve 
     * \App\Dtos\ContentIdeationDto içeren veri dizisi.
     * @param string $ideationSummary Operasyonun özet mesajı.
     */
    public function __construct(
        public array $contentIdeas,
        public string $ideationSummary
    ) {}
}