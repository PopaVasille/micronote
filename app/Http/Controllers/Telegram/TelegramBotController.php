<?php
// app/Http/Controllers/TelegramBotController.php
namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Http\Controllers\IncomingMessageController;
use Telegram\Bot\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramBotController extends Controller
{
    protected Api $telegram;

    /**
     * @throws TelegramSDKException
     */
    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
    }

    /**
     * @throws TelegramSDKException
     */
    public function handleWebhook(Request $request)
    {
        $update = $this->telegram->getWebhookUpdate();
        Log::info('intru prin webhook: '.json_encode($update));
// Verificăm dacă este o comandă /start
        if (isset($update['message']['text']) && $update['message']['text'] === '/start') {
            $telegramId = $update['message']['from']['id'];
            $response = "Salut! ID-ul tău Telegram este: $telegramId\n\n";
            $response .= "Copiază acest ID și introdu-l în aplicația MicroNote pentru a conecta contul tău.";

            $this->telegram->sendMessage([
                'chat_id' => $update['message']['chat']['id'],
                'text' => $response
            ]);
        }

        // Apelează și serviciul de procesare a mesajelor
        // Doar mesajele care nu sunt comenzi (pentru a evita duplicările)
        if (isset($update['message']['text']) && $update['message']['text'] !== '/start') {
            $incomingMessageController = app(IncomingMessageController::class);
            $incomingMessageController->handleTelegramWebhook($request);
        }
        Log::info('ies din webhook: '.json_encode($update));
        return response()->json(['status' => 'success']);
    }

    /**
     * @throws TelegramSDKException
     */
    public function setWebhook()
    {
        $result = $this->telegram->setWebhook([
            'url' => route('telegram.webhook')
        ]);

        return response()->json(['success' => $result]);
    }
}
