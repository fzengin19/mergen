<?php

namespace App\Dtos;

use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;

class StartResearchDto 
{
    #[SchemaProperty(description: 'The user query of the research.')]
    #[NotBlank]
    public string $query;
    
    #[SchemaProperty(description: 'The additional data to add to the research.')]
    public string $additionalData = '';

}