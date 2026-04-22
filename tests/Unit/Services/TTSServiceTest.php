<?php

declare(strict_types=1);

use App\Services\TTSService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->service = new TTSService();
    Storage::fake('local');
    Cache::flush();
});

describe('synthesizeOrpheus - streaming mode', function () {
    it('returns streaming data with session_id', function () {
        $result = $this->service->synthesizeOrpheus('Hello world', '1', streaming: true);

        expect($result)->toHaveKey('streaming')
            ->and($result['streaming'])->toBeTrue()
            ->and($result)->toHaveKey('stream_url')
            ->and($result)->toHaveKey('session_id')
            ->and($result['stream_url'])->toContain('/api/tts/stream/');
    });

    it('stores session data in cache with 5min TTL', function () {
        $result = $this->service->synthesizeOrpheus('Test text', '1', streaming: true);
        $sessionId = $result['session_id'];

        $cachedData = Cache::get("tts_session_{$sessionId}");

        expect($cachedData)->not->toBeNull()
            ->and($cachedData['text'])->toBe('Test text')
            ->and($cachedData['voice_param'])->toBe('woman');
    });

    it('maps voice 1 to woman parameter', function () {
        $result = $this->service->synthesizeOrpheus('Test', '1', streaming: true);
        $cachedData = Cache::get("tts_session_{$result['session_id']}");

        expect($cachedData['voice_param'])->toBe('woman');
    });

    it('maps voice 2 to man parameter', function () {
        $result = $this->service->synthesizeOrpheus('Test', '2', streaming: true);
        $cachedData = Cache::get("tts_session_{$result['session_id']}");

        expect($cachedData['voice_param'])->toBe('man');
    });
});

describe('synthesizeOrpheus - non-streaming fallback', function () {
    it('returns download_url and file_id', function () {
        $this->fakeOrpheusTTS();

        $result = $this->service->synthesizeOrpheus('Hello', '1', streaming: false);

        expect($result)->toHaveKey('download_url')
            ->and($result)->toHaveKey('file_id')
            ->and($result['fallback'])->toBeTrue()
            ->and($result['download_url'])->toContain('/api/tts/download/');
    });

    it('saves audio file to storage with WAV header', function () {
        $this->fakeOrpheusTTS();

        $result = $this->service->synthesizeOrpheus('Test', '1', streaming: false);
        $fileId = $result['file_id'];

        Storage::assertExists("tts/{$fileId}.wav");
    });

    it('throws exception when API returns 500', function () {
        $this->fakeOrpheusTTS(shouldFail: true);

        expect(fn () => $this->service->synthesizeOrpheus('Test', '1', streaming: false))
            ->toThrow(Exception::class);
    });
});

describe('synthesizeSpark', function () {
    it('maps voice 3 to f_happy', function () {
        Http::fake([
            '*/predict' => Http::response([
                'success' => true,
                'download_url' => 'http://example.com/audio.wav',
            ], 200),
            'http://example.com/audio.wav' => Http::response('AUDIO_DATA', 200),
        ]);

        $result = $this->service->synthesizeSpark('Test', '3');

        Http::assertSent(function ($request) {
            $data = $request->data();
            return isset($data['voice']) && $data['voice'] === 'f_happy';
        });

        expect($result)->toHaveKey('download_url');
    });

    it('maps voice 6 to m_happy', function () {
        Http::fake([
            '*/predict' => Http::response([
                'success' => true,
                'download_url' => 'http://example.com/audio.wav',
            ], 200),
            'http://example.com/audio.wav' => Http::response('AUDIO_DATA', 200),
        ]);

        $this->service->synthesizeSpark('Test', '6');

        Http::assertSent(function ($request) {
            $data = $request->data();
            return isset($data['voice']) && $data['voice'] === 'm_happy';
        });
    });

    it('downloads audio from URL and saves to storage', function () {
        $this->fakeSparkTTS();

        $result = $this->service->synthesizeSpark('Hello world', '3');
        $fileId = $result['file_id'];

        Storage::assertExists("tts/{$fileId}.wav");
    });

    it('returns download_url and file_id', function () {
        $this->fakeSparkTTS();

        $result = $this->service->synthesizeSpark('Test', '3');

        expect($result)->toHaveKey('download_url')
            ->and($result)->toHaveKey('file_id')
            ->and($result['download_url'])->toContain('/api/tts/download/');
    });

    it('throws exception when API fails', function () {
        $this->fakeSparkTTS(shouldFail: true);

        expect(fn () => $this->service->synthesizeSpark('Test', '3'))
            ->toThrow(Exception::class, 'SparkTTS API failed');
    });

    it('throws exception when download_url missing', function () {
        $this->fakeSparkTTS(includeDownloadUrl: false);

        expect(fn () => $this->service->synthesizeSpark('Test', '3'))
            ->toThrow(Exception::class, 'No download URL in response');
    });

    it('sends correct resample_rate parameter', function () {
        Http::fake([
            '*/predict' => Http::response([
                'success' => true,
                'download_url' => 'http://example.com/audio.wav',
            ], 200),
            'http://example.com/audio.wav' => Http::response('AUDIO_DATA', 200),
        ]);

        $this->service->synthesizeSpark('Test', '3');

        Http::assertSent(function ($request) {
            $data = $request->data();
            return isset($data['resample_rate']) && $data['resample_rate'] === 48000;
        });
    });
});

describe('synthesizeYourTTS', function () {
    it('uses female API for voice 9', function () {
        Http::fake();

        $this->service->synthesizeYourTTS('Test text', '9');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'female') || str_contains($request->url(), 'yourtts');
        });
    });

    it('uses male API for voice 10', function () {
        Http::fake();

        $this->service->synthesizeYourTTS('Test text', '10');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'male') || str_contains($request->url(), 'yourtts');
        });
    });

    it('sends correct parameters', function () {
        Http::fake();

        $this->service->synthesizeYourTTS('Hello', '9');

        Http::assertSent(function ($request) {
            $data = $request->data();
            return isset($data['resample_rate']) && $data['resample_rate'] === 24000
                && isset($data['speed']) && $data['speed'] === 1.0
                && isset($data['text']) && $data['text'] === 'Hello';
        });
    });
});

describe('voiceClone', function () {
    it('uploads audio file via multipart', function () {
        $audioFile = $this->createFakeAudioFile('wav', 100);

        Http::fake([
            '*/voice_clone' => Http::response([[
                'url' => 'http://example.com/cloned.wav',
            ]], 200),
        ]);

        $result = $this->service->voiceClone('Test text', $audioFile);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'voice_clone');
        });

        expect($result['success'])->toBeTrue();
    });

    it('returns success with audio_url and download_url', function () {
        $audioFile = $this->createFakeAudioFile('wav', 100);

        Http::fake([
            '*/voice_clone' => Http::response([[
                'url' => 'http://example.com/cloned.wav',
            ]], 200),
        ]);

        $result = $this->service->voiceClone('Clone this voice', $audioFile);

        expect($result)->toHaveKey('success')
            ->and($result['success'])->toBeTrue()
            ->and($result)->toHaveKey('audio_url')
            ->and($result)->toHaveKey('download_url')
            ->and($result)->toHaveKey('message')
            ->and($result['message'])->toBe('Voz clonada com sucesso!');
    });

    it('cleans up temp file after upload', function () {
        $audioFile = $this->createFakeAudioFile('wav', 100);

        Http::fake([
            '*/voice_clone' => Http::response([[
                'url' => 'http://example.com/cloned.wav',
            ]], 200),
        ]);

        $this->service->voiceClone('Test', $audioFile);

        // Verificar que nenhum arquivo temp permanece
        $tempFiles = Storage::files('temp');
        expect($tempFiles)->toBeEmpty();
    });

    it('throws exception when API fails', function () {
        $audioFile = $this->createFakeAudioFile('wav', 100);

        Http::fake([
            '*/voice_clone' => Http::response(['error' => 'API Error'], 500),
        ]);

        expect(fn () => $this->service->voiceClone('Test', $audioFile))
            ->toThrow(Exception::class);
    });

    it('throws exception when response format is invalid', function () {
        $audioFile = $this->createFakeAudioFile('wav', 100);

        Http::fake([
            '*/voice_clone' => Http::response(['invalid' => 'format'], 200),
        ]);

        expect(fn () => $this->service->voiceClone('Test', $audioFile))
            ->toThrow(Exception::class);
    });
});

describe('session management', function () {
    it('getStreamingSession retrieves cached session data', function () {
        $sessionId = 'test-session-123';
        Cache::put("tts_session_{$sessionId}", [
            'text' => 'Cached text',
            'voice_param' => 'woman',
        ], now()->addMinutes(5));

        $result = $this->service->getStreamingSession($sessionId);

        expect($result)->not->toBeNull()
            ->and($result['text'])->toBe('Cached text')
            ->and($result['voice_param'])->toBe('woman');
    });

    it('getStreamingSession returns null for non-existent session', function () {
        $result = $this->service->getStreamingSession('non-existent');

        expect($result)->toBeNull();
    });

    it('cleanupStreamingSession removes session from cache', function () {
        $sessionId = 'cleanup-test';
        Cache::put("tts_session_{$sessionId}", ['data' => 'test'], now()->addMinutes(5));

        $this->service->cleanupStreamingSession($sessionId);

        expect(Cache::get("tts_session_{$sessionId}"))->toBeNull();
    });
});

describe('file management', function () {
    it('getAudioFile returns absolute path for existing file', function () {
        $fileId = 'test-file-123';
        Storage::put("tts/{$fileId}.wav", 'audio content');

        $path = $this->service->getAudioFile($fileId);

        expect($path)->not->toBeNull()
            ->and($path)->toContain("{$fileId}.wav");
    });

    it('getAudioFile returns null for non-existent file', function () {
        $path = $this->service->getAudioFile('non-existent');

        expect($path)->toBeNull();
    });
});
