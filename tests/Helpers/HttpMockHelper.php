<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Support\Facades\Http;

trait HttpMockHelper
{
    /**
     * Mock Orpheus TTS API (streaming).
     *
     * @param bool $shouldFail Whether the API should return an error
     * @return void
     */
    protected function fakeOrpheusTTS(bool $shouldFail = false): void
    {
        if ($shouldFail) {
            Http::fake([
                '*/v1/audio/speech/stream' => Http::response(['error' => 'API Error'], 500),
            ]);
        } else {
            Http::fake([
                '*/v1/audio/speech/stream' => Http::response(
                    str_repeat('x', 1000), // Simula PCM audio data
                    200,
                    ['Content-Type' => 'audio/pcm']
                ),
            ]);
        }
    }

    /**
     * Mock SparkTTS/MIRA API (predict + download).
     *
     * @param bool $shouldFail Whether the API should return an error
     * @param bool $includeDownloadUrl Whether to include download_url in response
     * @return void
     */
    protected function fakeSparkTTS(bool $shouldFail = false, bool $includeDownloadUrl = true): void
    {
        if ($shouldFail) {
            Http::fake([
                '*/predict' => Http::response(['error' => 'API Error'], 500),
            ]);
        } elseif (!$includeDownloadUrl) {
            Http::fake([
                '*/predict' => Http::response(['success' => true], 200),
            ]);
        } else {
            Http::fake([
                '*/predict' => Http::response([
                    'success' => true,
                    'download_url' => 'http://example.com/audio.wav',
                ], 200),
                'http://example.com/audio.wav' => Http::response(
                    'FAKE_WAV_CONTENT',
                    200,
                    ['Content-Type' => 'audio/wav']
                ),
            ]);
        }
    }

    /**
     * Mock ChatGPT/OpenAI API.
     *
     * @param string $responseMessage The message to return in the response
     * @return void
     */
    protected function fakeChatGPT(string $responseMessage = 'Response from ChatGPT'): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => $responseMessage,
                        ],
                    ],
                ],
            ], 200),
        ]);
    }

    /**
     * Mock ChatGPT API failure.
     *
     * @return void
     */
    protected function fakeChatGPTFailure(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response(['error' => 'API Error'], 500),
        ]);
    }

    /**
     * Mock Google Gemini API.
     *
     * @param string $responseMessage The message to return in the response
     * @return void
     */
    protected function fakeGemini(string $responseMessage = 'Response from Gemini'): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => $responseMessage],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);
    }

    /**
     * Mock Gemini API failure.
     *
     * @return void
     */
    protected function fakeGeminiFailure(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response(['error' => 'API Error'], 500),
        ]);
    }

    /**
     * Mock ChatGPT failure with Gemini fallback success.
     *
     * @param string $geminiMessage The message to return from Gemini
     * @return void
     */
    protected function fakeChatGPTFailureWithGeminiFallback(string $geminiMessage = 'Response from Gemini (fallback)'): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response(['error' => 'OpenAI Error'], 500),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => $geminiMessage],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);
    }

    /**
     * Mock Biometria API with success responses.
     *
     * @return void
     */
    protected function fakeBiometriaSuccess(): void
    {
        Http::fake([
            '*/add_user' => Http::response([
                'success' => true,
                'user_id' => 'test-user-123',
                'message' => 'User added successfully',
            ], 200),
            '*/identification*' => Http::response([
                'success' => true,
                'user_id' => 'test-user-123',
                'confidence' => 0.95,
            ], 200),
            '*/verification*' => Http::response([
                'success' => true,
                'verified' => true,
                'confidence' => 0.98,
            ], 200),
            '*/delete_user' => Http::response([
                'success' => true,
                'message' => 'User deleted successfully',
            ], 200),
        ]);
    }

    /**
     * Mock Biometria API with deepfake detection blocked (403).
     *
     * @return void
     */
    protected function fakeBiometriaDeepfakeBlocked(): void
    {
        Http::fake([
            '*/add_user' => Http::response([
                'success' => false,
                'error' => 'Deepfake detected',
                'deepfake_score' => 0.88,
            ], 403),
            '*/identification*' => Http::response([
                'success' => false,
                'error' => 'Deepfake detected',
            ], 403),
            '*/verification*' => Http::response([
                'success' => false,
                'error' => 'Deepfake detected',
            ], 403),
        ]);
    }

    /**
     * Mock Deepfake Detection API.
     *
     * @param bool $isDeepfake Whether the audio is deepfake
     * @param float $score The deepfake score (0.0 to 1.0)
     * @return void
     */
    protected function fakeDeepfakeAPI(bool $isDeepfake = false, float $score = 0.2): void
    {
        Http::fake([
            '*/detect' => Http::response([
                'is_deepfake' => $isDeepfake,
                'deepfake_score' => $score,
            ], 200),
        ]);
    }

    /**
     * Mock Deepfake API failure.
     *
     * @return void
     */
    protected function fakeDeepfakeAPIFailure(): void
    {
        Http::fake([
            '*/detect' => Http::response(['error' => 'API Error'], 500),
        ]);
    }
}
