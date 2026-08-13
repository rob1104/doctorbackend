<?php

namespace App\AI\Services;

use App\AI\Contracts\AIServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService implements AIServiceInterface
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.ai.api_key', '');
        $this->model = config('services.ai.model', 'gemini-1.5-flash');
    }

    public function chat(array $messages, array $tools = []): array
    {
        // Transform OpenAI messages format to Gemini format
        $geminiMessages = [];
        $systemInstruction = null;

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemInstruction = [
                    'parts' => [['text' => $msg['content']]]
                ];
            } else if ($msg['role'] === 'user') {
                $geminiMessages[] = [
                    'role' => 'user',
                    'parts' => [['text' => $msg['content']]]
                ];
            } else if ($msg['role'] === 'assistant' && isset($msg['content']) && $msg['content']) {
                $geminiMessages[] = [
                    'role' => 'model',
                    'parts' => [['text' => $msg['content']]]
                ];
            } else if ($msg['role'] === 'assistant' && isset($msg['tool_calls'])) {
                $parts = [];
                foreach ($msg['tool_calls'] as $call) {
                    $parts[] = [
                        'functionCall' => [
                            'name' => $call['function']['name'],
                            'args' => json_decode($call['function']['arguments'], true)
                        ]
                    ];
                }
                $geminiMessages[] = [
                    'role' => 'model',
                    'parts' => $parts
                ];
            } else if ($msg['role'] === 'tool') {
                $geminiMessages[] = [
                    'role' => 'user',
                    'parts' => [
                        [
                            'functionResponse' => [
                                'name' => $msg['name'],
                                'response' => json_decode($msg['content'], true)
                            ]
                        ]
                    ]
                ];
            }
        }

        $payload = [
            'contents' => $geminiMessages,
            'generationConfig' => [
                'temperature' => (float) config('services.ai.temperature', 0.2),
            ]
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = $systemInstruction;
        }

        // Transform OpenAI tools format to Gemini format
        if (!empty($tools)) {
            $geminiTools = [];
            foreach ($tools as $tool) {
                $geminiTool = [
                    'name' => $tool['function']['name'],
                    'description' => $tool['function']['description'],
                ];
                
                if (isset($tool['function']['parameters'])) {
                    $geminiTool['parameters'] = $tool['function']['parameters'];
                }
                
                $geminiTools[] = $geminiTool;
            }
            
            $payload['tools'] = [
                ['functionDeclarations' => $geminiTools]
            ];
            
            // Force tool use if needed, but 'AUTO' is default in Gemini when tools are provided
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($url, $payload);

        if ($response->failed()) {
            Log::error('Gemini API Error: ' . $response->body());
            throw new \Exception('Error de comunicación con Gemini.');
        }

        $data = $response->json();

        if (!isset($data['candidates'][0]['content'])) {
            Log::error('Gemini Invalid Response: ' . json_encode($data));
            throw new \Exception('Respuesta inválida de Gemini.');
        }

        $content = $data['candidates'][0]['content'];
        $parts = $content['parts'] ?? [];

        // Transform back to OpenAI format for our Agent to process
        $result = [
            'role' => 'assistant',
            'content' => null,
        ];

        $toolCalls = [];
        
        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $result['content'] = $part['text'];
            } elseif (isset($part['functionCall'])) {
                $toolCalls[] = [
                    'id' => 'call_' . uniqid(),
                    'type' => 'function',
                    'function' => [
                        'name' => $part['functionCall']['name'],
                        'arguments' => json_encode($part['functionCall']['args'] ?? [])
                    ]
                ];
            }
        }

        if (!empty($toolCalls)) {
            $result['tool_calls'] = $toolCalls;
        }

        return $result;
    }
}
