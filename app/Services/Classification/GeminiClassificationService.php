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
     * @param  string  $messageContent
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
                Log::error('Gemini API error: '.$response->body());
                return $this->fallbackToRegexClassification($messageContent);
            }
        } catch (\Exception $e) {
            Log::error('Gemini classification failed: '.$e->getMessage());
            return $this->fallbackToRegexClassification($messageContent);
        }
    }

    /**
     * Construiește prompt-ul pentru clasificare
     *
     * @param  string  $messageContent
     * @return string
     */
    private function buildClassificationPrompt(string $messageContent): string
    {
        // We use a HEREDOC string for better readability of the prompt.
        return <<<PROMPT
                Ești un asistent expert în clasificare de text. Sarcina ta este să analizezezi mesajul utilizatorului și să îl clasifici în cea mai potrivită categorie din lista de mai jos.

            # CATEGORII DISPONIBILE:
            - task: O acțiune sau o sarcină specifică ce trebuie executată. Ceva ce trebuie "făcut".
            - idea: Un concept, un gând, o sugestie creativă sau o notă generală.
            - reminder: O notificare pentru a-ți aminti de ceva, adesea legată de un moment în timp.
            - shopping_list: O listă de produse sau articole de cumpărat.
            - event: O activitate programată, o întâlnire, o rezervare, cu dată, oră sau locație.
            - contact: Informații despre o persoană (nume, telefon, email).
            - recipe: Instrucțiuni de gătit, ingrediente pentru o rețetă.
            - bookmark: Un link web (URL) care trebuie salvat.
            - measurement: O valoare numerică cu o unitate de măsură (ex: cm, kg, m²).
            - simple: Orice mesaj care nu se încadrează clar în categoriile de mai sus.

            # EXEMPLE DE CLASIFICARE CORECTĂ:
            - Mesaj: "Trimite raportul lunar pana vineri." -> Răspuns: task
            - Mesaj: "Nu uita sa o suni pe mama maine la 12." -> Răspuns: reminder
            - Mesaj: "cumparaturi: lapte, paine, oua de la lidl" -> Răspuns: shopping_list
            - Mesaj: "ar fi misto sa facem un podcast despre istorie" -> Răspuns: idea
            - Mesaj: "Rezervare la Trattoria vineri la 19:30 pentru 4 persoane" -> Răspuns: event

            # MESAJ DE ANALIZAT:
            "$messageContent"

            # RĂSPUNS AȘTEPTAT:
            Răspunde DOAR cu numele categoriei (de ex: task, idea, reminder), cu litere mici și fără nicio altă explicație, text suplimentar sau punctuație.
            PROMPT;
    }

    /**
     * Parsează răspunsul de la Gemini API
     *
     * @param  array  $response
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
                'reminder' => Note::TYPE_REMINDER,
                'event' => Note::TYPE_EVENT,
                'contact' => Note::TYPE_CONTACT,
                'recipe' => Note::TYPE_RECIPE,
                'bookmark' => NOTE::TYPE_BOOKMARK,
                'measurement' => Note::TYPE_MEASUREMENT,
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
            Log::error('Error parsing Gemini response: '.$e->getMessage());
            return Note::TYPE_SIMPLE;
        }
    }

    /**
     * Fallback la clasificarea regex când Gemini nu funcționează
     *
     * @param  string  $messageContent
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
