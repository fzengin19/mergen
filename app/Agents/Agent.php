<?php

declare(strict_types=1);

namespace App\Agents;

use App\Models\Agent as AgentModel; // Eloquent Model'i import et
use NeuronAI\Agent as NeuronAgent;
use NeuronAI\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;

class Agent extends NeuronAgent
{
    public function __construct(private AgentModel $agentModel) {}

    /**
     * Agent'ın hangi LLM sağlayıcısını (OpenAI, Anthropic vb.) kullanacağını
     * veritabanındaki 'model' alanına göre belirler.
     */
    protected function provider(): AIProviderInterface
    {
        $provider = $this->agentModel->provider;
        if(class_exists($provider)) {
            return new $provider($this->agentModel->apiKey, $this->agentModel->model);
        }
        throw new \Exception('Provider not found');
    }

    /**
     * A
     */
    public function instructions(): string
    {
        // background, steps, output, toolsUsage
        return (string) new SystemPrompt(
            background: [$this->agentModel->background],
            steps: [$this->agentModel->steps],
            output: [$this->agentModel->output],
        );
    }

    /**
     * 
     */
    public function tools(): array
    {
        return [
            //Example App\Neuron\GetTranscriptionTool::class,
        ];
    }
}
