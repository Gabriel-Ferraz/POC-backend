<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BiometriaService
{
    /**
     * Register new user in biometrics system
     */
    public function addUser(
        string $userId,
        UploadedFile|array $audioFiles,
        ?string $name = null,
        ?string $email = null,
        bool $detectDeepfake = true,
        float $deepfakeThreshold = 0.5
    ): array {
        try {
            $apiUrl = config('external_apis.biometria.base_url');
            $files = is_array($audioFiles) ? $audioFiles : [$audioFiles];

            // Prepare multipart data
            $multipart = [
                ['name' => 'user_id', 'contents' => $userId],
                ['name' => 'detect_deepfake', 'contents' => $detectDeepfake ? 'true' : 'false'],
            ];

            if ($name) {
                $multipart[] = ['name' => 'name', 'contents' => $name];
            }

            if ($email) {
                $multipart[] = ['name' => 'email', 'contents' => $email];
            }

            if ($detectDeepfake) {
                $multipart[] = ['name' => 'deepfake_threshold', 'contents' => (string) $deepfakeThreshold];
            }

            // Process and add audio files
            foreach ($files as $index => $audioFile) {
                $convertedPath = $this->convertAudioToWav($audioFile);
                $multipart[] = [
                    'name' => 'audio',
                    'contents' => fopen(Storage::path($convertedPath), 'r'),
                    'filename' => "audio_{$index}.wav",
                ];
            }

            $response = Http::timeout(60)
                ->asMultipart()
                ->post("{$apiUrl}/add_user", $multipart);

            // Cleanup temp files
            foreach ($multipart as $part) {
                if (isset($part['contents']) && is_resource($part['contents'])) {
                    fclose($part['contents']);
                }
            }

            if ($response->status() === 403) {
                return [
                    'success' => false,
                    'error' => 'Cadastro bloqueado: áudio identificado como sintético ou manipulado',
                    'deepfake_blocked' => true,
                    'details' => $response->json(),
                ];
            }

            if (!$response->successful()) {
                throw new \Exception('Biometria API failed: ' . $response->body());
            }

            return $this->sanitizeNaN($response->json());
        } catch (\Exception $e) {
            Log::error('Biometria Add User Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Identify user by voice
     */
    public function identification(
        UploadedFile $audioFile,
        ?float $threshold = null,
        bool $detectDeepfake = true,
        float $deepfakeThreshold = 0.5
    ): array {
        try {
            $apiUrl = config('external_apis.biometria.base_url');
            $convertedPath = $this->convertAudioToWav($audioFile);

            $url = "{$apiUrl}/identification?detect_deepfake=" . ($detectDeepfake ? 'true' : 'false');

            if ($threshold) {
                $url .= "&threshold={$threshold}";
            }

            if ($detectDeepfake) {
                $url .= "&deepfake_threshold={$deepfakeThreshold}";
            }

            $response = Http::timeout(30)
                ->attach('audio', file_get_contents(Storage::path($convertedPath)), 'audio.wav')
                ->post($url);

            Storage::delete($convertedPath);

            if ($response->status() === 403) {
                return [
                    'success' => false,
                    'error' => 'Identificação bloqueada: áudio identificado como sintético ou manipulado',
                    'deepfake_blocked' => true,
                    'details' => $response->json(),
                ];
            }

            if (!$response->successful()) {
                throw new \Exception('Biometria API failed');
            }

            return $this->sanitizeNaN($response->json());
        } catch (\Exception $e) {
            Log::error('Biometria Identification Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify if audio belongs to specific user
     */
    public function verification(
        string $userId,
        UploadedFile $audioFile,
        ?float $threshold = null,
        bool $detectDeepfake = true,
        float $deepfakeThreshold = 0.5
    ): array {
        try {
            $apiUrl = config('external_apis.biometria.base_url');
            $convertedPath = $this->convertAudioToWav($audioFile);

            $url = "{$apiUrl}/verification?user_id={$userId}&detect_deepfake=" . ($detectDeepfake ? 'true' : 'false');

            if ($threshold) {
                $url .= "&threshold={$threshold}";
            }

            if ($detectDeepfake) {
                $url .= "&deepfake_threshold={$deepfakeThreshold}";
            }

            $response = Http::timeout(30)
                ->attach('audio', file_get_contents(Storage::path($convertedPath)), 'audio.wav')
                ->post($url);

            Storage::delete($convertedPath);

            if ($response->status() === 403) {
                return [
                    'success' => false,
                    'error' => 'Verificação bloqueada: áudio identificado como sintético ou manipulado',
                    'deepfake_blocked' => true,
                    'details' => $response->json(),
                ];
            }

            if (!$response->successful()) {
                throw new \Exception('Biometria API failed');
            }

            return $this->sanitizeNaN($response->json());
        } catch (\Exception $e) {
            Log::error('Biometria Verification Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete user from biometrics system
     */
    public function deleteUser(string $userId): array
    {
        try {
            $apiUrl = config('external_apis.biometria.base_url');

            $response = Http::timeout(30)
                ->delete("{$apiUrl}/delete_user", [
                    'user_id' => $userId,
                ]);

            if (!$response->successful()) {
                throw new \Exception('Biometria API failed');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Biometria Delete User Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if audio is deepfake
     */
    public function checkDeepfake(UploadedFile $audioFile, float $threshold = 0.5): array
    {
        try {
            $apiUrl = config('external_apis.deepfake.base_url');
            $convertedPath = $this->convertAudioToWav($audioFile);

            $response = Http::timeout(60)
                ->attach('file', file_get_contents(Storage::path($convertedPath)), 'audio.wav')
                ->post("{$apiUrl}/detect");

            Storage::delete($convertedPath);

            if (!$response->successful()) {
                throw new \Exception('Deepfake API failed');
            }

            return $this->sanitizeNaN($response->json());
        } catch (\Exception $e) {
            Log::error('Deepfake Check Error: ' . $e->getMessage());

            return [
                'error' => 'Erro ao processar arquivo de áudio',
                'is_deepfake' => false,
                'deepfake_score' => 0.0,
            ];
        }
    }

    /**
     * Convert audio file to WAV PCM 16-bit 16kHz
     */
    private function convertAudioToWav(UploadedFile $audioFile): string
    {
        // If already WAV, save directly
        if ($audioFile->getClientOriginalExtension() === 'wav') {
            $path = $audioFile->store('temp');

            return $path;
        }

        // Save original file
        $originalPath = $audioFile->store('temp');
        $originalFullPath = Storage::path($originalPath);

        // Convert using ffmpeg
        $convertedPath = 'temp/' . Str::uuid() . '.wav';
        $convertedFullPath = Storage::path($convertedPath);

        $command = sprintf(
            'ffmpeg -i %s -acodec pcm_s16le -ar 16000 -ac 1 -f wav -y %s 2>&1',
            escapeshellarg($originalFullPath),
            escapeshellarg($convertedFullPath)
        );

        exec($command, $output, $returnCode);

        // Cleanup original file
        Storage::delete($originalPath);

        if ($returnCode !== 0) {
            Log::error('FFmpeg conversion failed: ' . implode("\n", $output));
            throw new \Exception('Erro ao converter áudio para WAV');
        }

        return $convertedPath;
    }

    /**
     * Replace NaN values in array (recursive)
     */
    private function sanitizeNaN($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeNaN'], $data);
        }

        if (is_float($data) && is_nan($data)) {
            return 0.0;
        }

        return $data;
    }
}
