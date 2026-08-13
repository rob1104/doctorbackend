<?php

namespace App\AI\Services;

use App\AI\Contracts\AIServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService implements AIServiceInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $provider;

    public function __construct()
    {
        // Si el proveedor no es OpenAI, pero usamos el mismo protocolo, se puede cambiar la URL
        $this->provider = config('services.ai.provider', 'openai');
        $this->apiKey = config('services.ai.api_key', '');
        $this->model = config('services.ai.model', 'gpt-4o-mini');
    }

    public function chat(array $messages, array $tools = []): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => (float) config('services.ai.temperature', 0.2),
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $url = 'https://api.openai.com/v1/chat/completions';
        
        // Soporte para Gemini a través de su API compatible con OpenAI
        if ($this->provider === 'gemini') {
            $url = "https://generativelanguage.googleapis.com/v1beta/openai/chat/completions";
        } elseif ($this->provider !== 'openai') {
            $url = config('services.ai.base_url', $url);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(60)->post($url, $payload);

        if ($response->failed()) {
            Log::error('AI API Error: ' . $response->body());
            throw new \Exception('Error de comunicación con el proveedor de IA.');
        }

        $data = $response->json();

        if (!isset($data['choices'][0]['message'])) {
            Log::error('AI Invalid Response: ' . json_encode($data));
            throw new \Exception('Respuesta inválida del proveedor de IA.');
        }

        return $data['choices'][0]['message'];
    }
}
