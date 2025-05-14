<?php
// app/Http/Controllers/TelegramBotController.php
namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
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
