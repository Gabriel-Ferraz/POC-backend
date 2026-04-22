<?php

namespace App\Http\Controllers\Voice;

use App\Http\Controllers\Controller;
use App\Services\BiometriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BiometriaController extends Controller
{
    private BiometriaService $biometriaService;

    public function __construct(BiometriaService $biometriaService)
    {
        $this->biometriaService = $biometriaService;
    }

    /**
     * Register new user in biometrics system
     */
    public function addUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'audio' => 'required|file|mimes:wav,mp3,webm,ogg|max:20480', // 20MB max
            'detect_deepfake' => 'nullable|boolean',
            'deepfake_threshold' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados de entrada inválidos',
                'details' => $validator->errors(),
            ], HttpResponse::HTTP_BAD_REQUEST);
        }

        try {
            $data = $validator->validated();

            $result = $this->biometriaService->addUser(
                userId: $data['user_id'],
                audioFiles: $request->file('audio'),
                name: $data['name'] ?? null,
                email: $data['email'] ?? null,
                detectDeepfake: $data['detect_deepfake'] ?? true,
                deepfakeThreshold: $data['deepfake_threshold'] ?? 0.5
            );

            if (isset($result['deepfake_blocked']) && $result['deepfake_blocked']) {
                return response()->json($result, HttpResponse::HTTP_FORBIDDEN);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Biometria Add User Error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Erro no cadastro: ' . $e->getMessage(),
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Identify user by voice
     */
    public function identification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'audio' => 'required|file|mimes:wav,mp3,webm,ogg|max:20480',
            'threshold' => 'nullable|numeric|min:0|max:1',
            'detect_deepfake' => 'nullable|boolean',
            'deepfake_threshold' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Arquivo de áudio é obrigatório',
                'details' => $validator->errors(),
            ], HttpResponse::HTTP_BAD_REQUEST);
        }

        try {
            $data = $validator->validated();

            $result = $this->biometriaService->identification(
                audioFile: $request->file('audio'),
                threshold: $data['threshold'] ?? null,
                detectDeepfake: $data['detect_deepfake'] ?? true,
                deepfakeThreshold: $data['deepfake_threshold'] ?? 0.5
            );

            if (isset($result['deepfake_blocked']) && $result['deepfake_blocked']) {
                return response()->json($result, HttpResponse::HTTP_FORBIDDEN);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Biometria Identification Error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Erro na identificação: ' . $e->getMessage(),
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verify if audio belongs to specific user
     */
    public function verification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string|max:255',
            'audio' => 'required|file|mimes:wav,mp3,webm,ogg|max:20480',
            'threshold' => 'nullable|numeric|min:0|max:1',
            'detect_deepfake' => 'nullable|boolean',
            'deepfake_threshold' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'ID do usuário e arquivo de áudio são obrigatórios',
                'details' => $validator->errors(),
            ], HttpResponse::HTTP_BAD_REQUEST);
        }

        try {
            $data = $validator->validated();

            $result = $this->biometriaService->verification(
                userId: $data['user_id'],
                audioFile: $request->file('audio'),
                threshold: $data['threshold'] ?? null,
                detectDeepfake: $data['detect_deepfake'] ?? true,
                deepfakeThreshold: $data['deepfake_threshold'] ?? 0.5
            );

            if (isset($result['deepfake_blocked']) && $result['deepfake_blocked']) {
                return response()->json($result, HttpResponse::HTTP_FORBIDDEN);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Biometria Verification Error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Erro na verificação: ' . $e->getMessage(),
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete user from biometrics system
     */
    public function deleteUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'ID do usuário é obrigatório',
                'details' => $validator->errors(),
            ], HttpResponse::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->biometriaService->deleteUser($request->input('user_id'));

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Biometria Delete User Error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Erro ao deletar: ' . $e->getMessage(),
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Check if audio is deepfake
     */
    public function checkDeepfake(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'audio' => 'required|file|mimes:wav,mp3,webm,ogg|max:20480',
            'threshold' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Arquivo de áudio é obrigatório',
                'details' => $validator->errors(),
            ], HttpResponse::HTTP_BAD_REQUEST);
        }

        try {
            $data = $validator->validated();

            $result = $this->biometriaService->checkDeepfake(
                audioFile: $request->file('audio'),
                threshold: $data['threshold'] ?? 0.5
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Deepfake Check Error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Erro ao processar arquivo de áudio',
                'is_deepfake' => false,
                'deepfake_score' => 0.0,
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
