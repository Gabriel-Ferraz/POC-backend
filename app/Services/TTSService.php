<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TTSService
{
    /**
     * Voice mapping for SparkTTS
     */
    private const VOICE_MAP = [
        // Original voices
        '3' => 'f_happy',
        '4' => 'f_angry',
        '5' => 'f_sad',
        '6' => 'm_happy',
        '7' => 'm_angry',
        '8' => 'm_sad',
        // Dubbing voices - female
        '11' => 'dub_fem11_feliz',
        '12' => 'dub_fem14_tristeza',
        '13' => 'dub_fem17_surpresa',
        '14' => 'dub_fem6_raiva',
        '15' => 'dub_fem8_medo',
        // Dubbing voices - male
        '16' => 'dub_masc5_raiva',
        '17' => 'dub_masc8_medo',
    ];

    /**
     * Synthesize speech using Orpheus TTS (NaturalVoice)
     */
    public function synthesizeOrpheus(string $text, string $voice, bool $streaming = false): array
    {
        try {
            $voiceParam = $voice === '1' ? 'woman' : 'man';
            $apiUrl = config('external_apis.tts.orpheus.streaming');

            if ($streaming) {
                return $this->setupOrpheusStreaming($text, $voiceParam);
            }

            // Non-streaming: use streaming fallback to get file
            return $this->orpheusStreamingFallback($text, $voiceParam);
        } catch (\Exception $e) {
            Log::error('Orpheus TTS Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Setup Orpheus streaming session
     */
    private function setupOrpheusStreaming(string $text, string $voiceParam): array
    {
        $sessionId = Str::uuid()->toString();

        // Store session data in cache (5 minutes)
        cache()->put("tts_session_{$sessionId}", [
            'text' => $text,
            'voice_param' => $voiceParam,
            'api_url' => config('external_apis.tts.orpheus.streaming'),
        ], now()->addMinutes(5));

        return [
            'streaming' => true,
            'stream_url' => "/api/tts/stream/{$sessionId}",
            'session_id' => $sessionId,
        ];
    }

    /**
     * Orpheus streaming fallback - downloads audio and saves to file
     */
    private function orpheusStreamingFallback(string $text, string $voiceParam): array
    {
        $apiUrl = config('external_apis.tts.orpheus.streaming');

        $response = Http::timeout(60)
            ->withBody(json_encode([
                'input' => $text,
                'voice' => $voiceParam,
            ]), 'application/json')
            ->post("{$apiUrl}/v1/audio/speech/stream");

        if (!$response->successful()) {
            throw new \Exception('Failed to synthesize audio');
        }

        // Save audio to storage
        $fileId = Str::uuid()->toString();
        $fileName = "tts/{$fileId}.wav";

        // Generate WAV header
        $audioData = $response->body();
        $wavHeader = $this->generateWavHeader(strlen($audioData));

        Storage::put($fileName, $wavHeader . $audioData);

        return [
            'download_url' => "/api/tts/download/{$fileId}",
            'file_id' => $fileId,
            'fallback' => true,
        ];
    }

    /**
     * Synthesize speech using SparkTTS/MIRA (ExpressiveVoice)
     */
    public function synthesizeSpark(string $text, string $voice): array
    {
        try {
            $sparkVoice = self::VOICE_MAP[$voice] ?? 'f_happy';
            $apiUrl = config('external_apis.tts.mira.base_url');

            $response = Http::timeout(30)
                ->post("{$apiUrl}/predict", [
                    'text' => $text,
                    'voice' => $sparkVoice,
                    'resample_rate' => 48000,
                ]);

            if (!$response->successful()) {
                throw new \Exception('SparkTTS API failed');
            }

            $data = $response->json();

            if (!isset($data['download_url'])) {
                throw new \Exception('No download URL in response');
            }

            // Download the audio file
            $audioResponse = Http::timeout(30)->get($data['download_url']);

            if (!$audioResponse->successful()) {
                throw new \Exception('Failed to download audio from SparkTTS');
            }

            // Save to storage
            $fileId = Str::uuid()->toString();
            $fileName = "tts/{$fileId}.wav";
            Storage::put($fileName, $audioResponse->body());

            return [
                'download_url' => "/api/tts/download/{$fileId}",
                'file_id' => $fileId,
            ];
        } catch (\Exception $e) {
            Log::error('SparkTTS Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Synthesize speech using YourTTS (QuickSynth)
     */
    public function synthesizeYourTTS(string $text, string $voice): array
    {
        try {
            // Voice 9 = female, Voice 10 = male
            $apiUrl = $voice === '9'
                ? config('external_apis.tts.yourtts.female')
                : config('external_apis.tts.yourtts.male');

            $response = Http::timeout(30)
                ->post("{$apiUrl}/predict", [
                    'text' => $text,
                    'resample_rate' => 24000,
                    'speed' => 1.0,
                    'output_format' => 'wav',
                ]);

            if (!$response->successful()) {
                throw new \Exception('YourTTS API failed');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('YourTTS Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Voice cloning using MIRA TTS
     */
    public function voiceClone(string $text, $audioFile): array
    {
        try {
            $apiUrl = config('external_apis.tts.mira.base_url');

            // Save uploaded file temporarily
            $tempPath = $audioFile->store('temp');
            $fullPath = Storage::path($tempPath);

            $response = Http::timeout(60)
                ->attach('audio', file_get_contents($fullPath), $audioFile->getClientOriginalName())
                ->post("{$apiUrl}/voice_clone", [
                    'text' => $text,
                ]);

            // Cleanup temp file
            Storage::delete($tempPath);

            if (!$response->successful()) {
                throw new \Exception('Voice clone API failed');
            }

            $result = $response->json();

            if (is_array($result) && isset($result[0]['url'])) {
                return [
                    'success' => true,
                    'audio_url' => $result[0]['url'],
                    'download_url' => $result[0]['url'],
                    'message' => 'Voz clonada com sucesso!',
                ];
            }

            throw new \Exception('Unexpected API response format');
        } catch (\Exception $e) {
            Log::error('Voice Clone Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate WAV header for PCM audio data
     */
    private function generateWavHeader(int $dataSize, int $sampleRate = 24000, int $bitsPerSample = 16, int $channels = 1): string
    {
        $bytesPerSample = $bitsPerSample / 8;
        $blockAlign = $bytesPerSample * $channels;
        $byteRate = $sampleRate * $blockAlign;
        $fileSize = 36 + $dataSize;

        return pack(
            'a4Va4a4VvvVVvva4V',
            'RIFF',
            $fileSize,
            'WAVE',
            'fmt ',
            16,
            1,
            $channels,
            $sampleRate,
            $byteRate,
            $blockAlign,
            $bitsPerSample,
            'data',
            $dataSize
        );
    }

    /**
     * Get streaming session data
     */
    public function getStreamingSession(string $sessionId): ?array
    {
        return cache()->get("tts_session_{$sessionId}");
    }

    /**
     * Clean up streaming session
     */
    public function cleanupStreamingSession(string $sessionId): void
    {
        cache()->forget("tts_session_{$sessionId}");
    }

    /**
     * Get audio file from storage
     */
    public function getAudioFile(string $fileId): ?string
    {
        $fileName = "tts/{$fileId}.wav";

        if (!Storage::exists($fileName)) {
            return null;
        }

        return Storage::path($fileName);
    }
}
