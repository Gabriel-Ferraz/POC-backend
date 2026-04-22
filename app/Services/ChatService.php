<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatService
{
    private TTSService $ttsService;

    public function __construct(TTSService $ttsService)
    {
        $this->ttsService = $ttsService;
    }

    /**
     * Send message to chat and get response with audio
     */
    public function sendMessage(string $message, string $voice = 'woman'): array
    {
        try {
            // Try ChatGPT first
            $response = $this->getChatGPTResponse($message);
            $apiUsed = 'ChatGPT';
        } catch (\Exception $e) {
            Log::warning('ChatGPT failed, falling back to Gemini: ' . $e->getMessage());

            try {
                // Fallback to Gemini
                $response = $this->getGeminiResponse($message);
                $apiUsed = 'Gemini (fallback)';
            } catch (\Exception $geminiError) {
                Log::error('Both ChatGPT and Gemini failed');
                throw new \Exception('Ambas APIs falharam. ChatGPT: ' . $e->getMessage() . ', Gemini: ' . $geminiError->getMessage());
            }
        }

        if (empty($response)) {
            throw new \Exception('Nenhuma resposta foi gerada');
        }

        // Setup audio streaming session
        $sessionId = Str::uuid()->toString();

        cache()->put("tts_session_{$sessionId}", [
            'text' => $response,
            'voice_param' => $voice,
            'api_url' => config('external_apis.tts.orpheus.streaming'),
        ], now()->addMinutes(5));

        return [
            'response' => $response,
            'audio_stream_url' => "/api/tts/stream/{$sessionId}",
            'session_id' => $sessionId,
            'api_used' => $apiUsed,
        ];
    }

    /**
     * Get response from ChatGPT
     */
    private function getChatGPTResponse(string $message): string
    {
        $apiKey = config('external_apis.chat.openai.api_key');
        $apiUrl = config('external_apis.chat.openai.api_url');
        $model = config('external_apis.chat.openai.model');

        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key not configured');
        }

        $response = Http::timeout(15)
            ->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])
            ->post($apiUrl, [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um atendente virtual prestativo e amigável da Ermis.ai, uma empresa de tecnologia de voz com IA. Responda de forma concisa e útil (máximo 2-3 frases).',
                    ],
                    [
                        'role' => 'user',
                        'content' => $message,
                    ],
                ],
                'max_tokens' => 150,
                'temperature' => 0.7,
            ]);

        if (!$response->successful()) {
            throw new \Exception('ChatGPT API failed: ' . $response->body());
        }

        $data = $response->json();

        return $data['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Get response from Gemini
     */
    private function getGeminiResponse(string $message): string
    {
        $apiKey = config('external_apis.chat.gemini.api_key');
        $apiUrl = config('external_apis.chat.gemini.api_url');

        if (empty($apiKey)) {
            throw new \Exception('Gemini API key not configured');
        }

        $response = Http::timeout(15)
            ->post("{$apiUrl}?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => "Você é um atendente virtual prestativo e amigável da Ermis.ai, uma empresa de tecnologia de voz com IA. Responda de forma concisa e útil (máximo 2-3 frases). Pergunta do cliente: {$message}",
                            ],
                        ],
                    ],
                ],
            ]);

        if (!$response->successful()) {
            throw new \Exception('Gemini API failed: ' . $response->body());
        }

        $data = $response->json();

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }
}
