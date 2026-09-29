<?php

namespace App\Http\Controllers\Max;

use App\Http\Controllers\Controller;
use App\Jobs\HandleMaxUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Приём событий бота MAX. Ответ должен быть 200 в течение 30 секунд,
 * поэтому обработка уходит в очередь.
 *
 * @see https://dev.max.ru/docs-api/methods/POST/subscriptions
 */
class WebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('services.max.webhook_secret');

        if ($secret !== '' && ! hash_equals($secret, (string) $request->header('X-Max-Bot-Api-Secret'))) {
            return response()->json(['success' => false, 'message' => 'Invalid secret'], 401);
        }

        $update = $request->all();

        if (filled($update['update_type'] ?? null)) {
            HandleMaxUpdate::dispatch($update);
        }

        return response()->json(['success' => true]);
    }
}
