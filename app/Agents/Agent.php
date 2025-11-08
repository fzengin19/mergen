<?php

declare(strict_types=1);

namespace App\Agents;

use App\Models\Agent as AgentModel; // Eloquent Model'i import et
use NeuronAI\Agent as NeuronAgent;
use NeuronAI\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Gemini\Gemini;
use NeuronAI\Providers\OpenAILike;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Tools\Toolkits\Tavily\TavilyToolkit;
use App\Tools\GoogleSearchTool;
use App\Tools\VisitPageTool;

class Agent extends NeuronAgent
{
    public function __construct(private AgentModel $agentModel) {
        $this->toolMaxTries(15);
    }

    /**
     * Agent'ın hangi LLM sağlayıcısını (OpenAI, Anthropic vb.) kullanacağını
     * veritabanındaki 'provider' alanına ve config ayarlarına göre belirler.
     */
    protected function provider(): AIProviderInterface
    {
        // Öncelik 1: Custom OpenAI provider (config'te enabled ise)
        $customOpenAI = config('services.custom_openai');
        if ($customOpenAI['enabled'] ?? false) {
            $apiKey = $customOpenAI['api_key'] ?? $this->agentModel->apiKey;
            $model = $customOpenAI['model'] ?? $this->agentModel->model ?? 'gpt-4';
            
            if ($apiKey && $customOpenAI['base_uri']) {
                return new OpenAILike(
                    baseUri: $customOpenAI['base_uri'],
                    key: $apiKey,
                    model: $model,
                    parameters: [],
                    strict_response: $customOpenAI['strict_response'] ?? false,
                    httpOptions: new HttpClientOptions(
                        timeout: (float)($customOpenAI['timeout'] ?? 30)
                    ),
                );
            }
        }

        // Öncelik 2: DB'deki provider class'ı (eğer varsa ve geçerliyse)
        $providerClass = $this->agentModel->provider;
        if ($providerClass && class_exists($providerClass)) {
            try {
                $apiKey = $this->agentModel->apiKey ?? env('GEMINI_API_KEY');
                $model = $this->agentModel->model ?? env('GEMINI_MODEL', 'gemini-1.5-pro');
                return new $providerClass($apiKey, $model);
            } catch (\Exception $e) {
                // Provider class varsa ama kurulumu başarısızsa fallback'e geç
            }
        }

        // Fallback: Gemini (default)
        return new Gemini(env('GEMINI_API_KEY'), env('GEMINI_MODEL', 'gemini-1.5-pro'));
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
     * Return available tools for this agent
     */
    public function tools(): array
    {
        $tools = [
            new GoogleSearchTool(),
            new VisitPageTool(),
        ];

        // Tavily toolkit ekle (API key varsa)
        $tavilyApiKey = config('services.tavily.api_key');
        if ($tavilyApiKey) {
            $tools[] = TavilyToolkit::make(
                key: $tavilyApiKey
            );
        }

        return $tools;
    }

    /**
     * Get the agent model instance
     */
    public function getModel(): AgentModel
    {
        return $this->agentModel;
    }
}
