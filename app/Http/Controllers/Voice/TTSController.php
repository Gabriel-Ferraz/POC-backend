<?php

namespace App\Http\Controllers\Voice;

use App\Http\Controllers\Controller;
use App\Services\TTSService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TTSController extends Controller
{
    private TTSService $ttsService;

    public function __construct(TTSService $ttsService)
    {
        $this->ttsService = $ttsService;
    }

    /**
     * Synthesize speech from text
     */
    public function synthesize(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:5000',
            'model' => 'required|in:NaturalVoice,ExpressiveVoice,QuickSynth',
            'voice' => 'required|string',
            'streaming' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados de entrada inválidos',
                'details' => $validator->errors(),
            ], HttpResponse::HTTP_BAD_REQUEST);
        }

        try {
            $data = $validator->validated();

            $result = match ($data['model']) {
                'NaturalVoice' => $this->ttsService->synthesizeOrpheus(
                    $data['text'],
                    $data['voice'],
                    $data['streaming'] ?? false
                ),
                'ExpressiveVoice' => $this->ttsService->synthesizeSpark(
                    $data['text'],
                    $data['voice']
                ),
                'QuickSynth' => $this->ttsService->synthesizeYourTTS(
                    $data['text'],
                    $data['voice']
                ),
            };

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('TTS Synthesize Error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Erro ao sintetizar áudio: ' . $e->getMessage(),
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Stream audio in real-time
     */
    public function stream(string $sessionId): StreamedResponse|JsonResponse
    {
        $session = $this->ttsService->getStreamingSession($sessionId);

        if (!$session) {
            return response()->json([
                'error' => 'Sessão não encontrada',
            ], HttpResponse::HTTP_NOT_FOUND);
        }

        return response()->stream(function () use ($session) {
            $apiUrl = $session['api_url'];
            $text = $session['text'];
            $voiceParam = $session['voice_param'];

            try {
                $response = \Illuminate\Support\Facades\Http::timeout(60)
                    ->withOptions(['stream' => true])
                    ->post("{$apiUrl}/v1/audio/speech/stream", [
                        'input' => $text,
                        'voice' => $voiceParam,
                    ]);

                // Send WAV header first
                $wavHeader = pack(
                    'a4Va4a4VvvVVvva4V',
                    'RIFF',
                    1000036,
                    'WAVE',
                    'fmt ',
                    16,
                    1,
                    1,
                    24000,
                    48000,
                    2,
                    16,
                    'data',
                    1000000
                );
                echo $wavHeader;
                flush();

                // Stream audio chunks
                foreach ($response->getBody() as $chunk) {
                    echo $chunk;
                    flush();
                }
            } catch (\Exception $e) {
                Log::error('TTS Streaming Error: ' . $e->getMessage());
            }
        }, 200, [
            'Content-Type' => 'audio/wav',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Cleanup streaming session
     */
    public function streamCleanup(string $sessionId): JsonResponse
    {
        $this->ttsService->cleanupStreamingSession($sessionId);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Download generated audio file
     */
    public function download(string $fileId)
    {
        $filePath = $this->ttsService->getAudioFile($fileId);

        if (!$filePath) {
            return response()->json([
                'error' => 'Arquivo não encontrado',
            ], HttpResponse::HTTP_NOT_FOUND);
        }

        return response()->download($filePath, "audio_{$fileId}.wav", [
            'Content-Type' => 'audio/wav',
        ]);
    }

    /**
     * Voice cloning
     */
    public function voiceClone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:5000',
            'audio' => 'required|file|mimes:wav,mp3,webm,ogg|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados de entrada inválidos',
                'details' => $validator->errors(),
            ], HttpResponse::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->ttsService->voiceClone(
                $request->input('text'),
                $request->file('audio')
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Voice Clone Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Erro na clonagem: ' . $e->getMessage(),
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get demo text for a specific voice
     */
    public function getDemoText(string $voiceId): JsonResponse
    {
        $voiceMap = [
            '3' => 'f_happy',
            '4' => 'f_angry',
            '5' => 'f_sad',
            '6' => 'm_happy',
            '7' => 'm_angry',
            '8' => 'm_sad',
            '11' => 'dub_fem11_feliz',
            '12' => 'dub_fem12_tristeza',
            '13' => 'dub_fem17_surpresa',
            '14' => 'dub_fem6_raiva',
            '15' => 'dub_fem7_medo',
            '16' => 'dub_masc5_raiva',
            '17' => 'dub_masc8_medo',
        ];

        $voiceKey = $voiceMap[$voiceId] ?? 'default';
        $demoText = config("external_apis.demo_texts.{$voiceKey}") ?? config('external_apis.demo_texts.default');

        return response()->json([
            'demo_text' => $demoText,
            'voice_id' => $voiceId,
            'voice_key' => $voiceKey,
        ]);
    }
}
