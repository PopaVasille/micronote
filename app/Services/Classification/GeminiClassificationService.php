<?php

namespace App\Services\Classification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Note;

class GeminiClassificationService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-lite:generateContent';
    }

    /**
     * Clasifică un mesaj folosind Gemini API
     *
     * @param string $messageContent
     * @return string
     */
    public function classifyMessage(string $messageContent): string
    {
        try {
            // Prompt-ul optimizat pentru clasificare
            $prompt = $this->buildClassificationPrompt($messageContent);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => $this->apiKey,
            ])->post($this->baseUrl, [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return $this->parseClassificationResponse($result);
            } else {
                Log::error('Gemini API error: ' . $response->body());
                return $this->fallbackToRegexClassification($messageContent);
            }

        } catch (\Exception $e) {
            Log::error('Gemini classification failed: ' . $e->getMessage());
            return $this->fallbackToRegexClassification($messageContent);
        }
    }

    /**
     * Construiește prompt-ul pentru clasificare
     *
     * @param string $messageContent
     * @return string
     */
    private function buildClassificationPrompt(string $messageContent): string
    {
        return "Analizează următorul mesaj și clasifică-l în una dintre aceste categorii: task, idea, shopping_list, event, contact, recipe, bookmark, measurement, simple.

Reguli de clasificare:
- task: acțiuni de făcut, sarcini, reminder-uri, lucruri urgente
- idea: concepte creative, sugestii, brainstorming, gânduri inovatoare
- shopping_list: liste de cumpărături, produse de achiziționat
- event: evenimente, rezervări, întâlniri, activități programate
- contact: mesaje despre persoane, informații de contact
- recipe: rețete, ingrediente, instrucțiuni de gătit
- bookmark: link-uri, resurse web, articole de salvat
- measurement: dimensiuni, măsurători, cantități
- simple: orice altceva care nu se încadrează în categoriile de mai sus

Mesaj de analizat: \"$messageContent\"

Răspunde DOAR cu numele categoriei (ex: task, idea, shopping_list, etc.), fără explicații suplimentare.";
    }

    /**
     * Parsează răspunsul de la Gemini API
     *
     * @param array $response
     * @return string
     */
    private function parseClassificationResponse(array $response): string
    {
        try {
            // Extrage textul din răspunsul Gemini
            $generatedText = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Curăță răspunsul și extrage doar categoria
            $classification = strtolower(trim($generatedText));

            // Lista categoriilor valide
            $validCategories = [
                'task' => Note::TYPE_TASK,
                'idea' => Note::TYPE_IDEA,
                'shopping_list' => Note::TYPE_SHOPING_LIST,
                'event' => 'event',
                'contact' => 'contact',
                'recipe' => 'recipe',
                'bookmark' => 'bookmark',
                'measurement' => 'measurement',
                'simple' => Note::TYPE_SIMPLE
            ];

            // Verifică dacă categoria e validă
            if (array_key_exists($classification, $validCategories)) {
                Log::info("Gemini classification successful: $classification");
                return $validCategories[$classification];
            }

            // Dacă răspunsul conține una din categorii (pentru cazuri când AI-ul adaugă text extra)
            foreach ($validCategories as $category => $type) {
                if (str_contains($classification, $category)) {
                    Log::info("Gemini classification found in text: $category");
                    return $type;
                }
            }

            Log::warning("Invalid Gemini classification response: $classification");
            return Note::TYPE_SIMPLE;

        } catch (\Exception $e) {
            Log::error('Error parsing Gemini response: ' . $e->getMessage());
            return Note::TYPE_SIMPLE;
        }
    }

    /**
     * Fallback la clasificarea regex când Gemini nu funcționează
     *
     * @param string $messageContent
     * @return string
     */
    private function fallbackToRegexClassification(string $messageContent): string
    {
        Log::info('Using regex fallback for classification');

        $regexService = app(MessageClassificationService::class);
        return $regexService->classifyMessage($messageContent);
    }

    /**
     * Verifică dacă serviciul Gemini e disponibil
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}
