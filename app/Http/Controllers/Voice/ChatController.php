<?php

namespace App\Http\Controllers\Voice;

use App\Http\Controllers\Controller;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    private ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Send message to chat bot and get response with audio
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
            'voice' => 'nullable|in:woman,man',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Mensagem é obrigatória',
                'details' => $validator->errors(),
            ], HttpResponse::HTTP_BAD_REQUEST);
        }

        try {
            $data = $validator->validated();
            $voice = $data['voice'] ?? 'woman';

            $result = $this->chatService->sendMessage($data['message'], $voice);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Chat Error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Erro no chat: ' . $e->getMessage(),
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
