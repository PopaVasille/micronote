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
     * Extracts shopping list items from a message using Gemini API.
     *
     * @param string $messageContent
     * @return array|null The structured shopping list items or null on failure.
     */
    public function extractShoppingListItems(string $messageContent): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $prompt = $this->buildShoppingListPrompt($messageContent);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => $this->apiKey,
            ])->post($this->baseUrl, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                // Force JSON output
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $jsonText = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if ($jsonText) {
                    $decoded = json_decode($jsonText, true);
                    if (isset($decoded['items']) && is_array($decoded['items'])) {
                        Log::info('Successfully extracted shopping items via AI.', $decoded['items']);
                        // Ensure all items have the 'completed' key
                        return array_map(function($item) {
                            return [
                                'text' => $item['text'] ?? 'unknown item',
                                'completed' => $item['completed'] ?? false
                            ];
                        }, $decoded['items']);
                    }
                }
            } else {
                Log::error('Gemini API error for shopping list extraction: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Gemini shopping list extraction failed: ' . $e->getMessage());
        }

        return null; // Return null if anything fails
    }

    /**
     * Builds the prompt for shopping list item extraction.
     *
     * @param string $messageContent
     * @return string
     */
    private function buildShoppingListPrompt(string $messageContent): string
    {
        return <<<PROMPT
        Analizează următorul text, care este o listă de cumpărături. Extrage fiecare item și returnează-l într-un format JSON.
        Fiecare item trebuie să fie un obiect cu cheile "text" (string) și "completed" (boolean, default false).

        Reguli importante:
        1. Ignoră orice text care nu pare a fi un item de cumpărături.
        2. Tratează cuvintele de legătură precum 'și', 'iar', 'cu' sau virgulele ca separatori de itemi.
        3. Asigură-te că un item extras NU începe cu aceste cuvinte de legătură.

        Exemplu 1:
        Text: "cumpără lapte, oua si branza"
        Răspuns JSON:
        {
          "items": [
            { "text": "lapte", "completed": false },
            { "text": "oua", "completed": false },
            { "text": "branza", "completed": false }
          ]
        }

        Exemplu 2:
        Text: "lista magazin: 2 paini, 1L de lapte iar la final hartie igienica"
        Răspuns JSON:
        {
          "items": [
            { "text": "2 paini", "completed": false },
            { "text": "1L de lapte", "completed": false },
            { "text": "hartie igienica", "completed": false }
          ]
        }

        Acum, analizează acest text și returnează DOAR formatul JSON:
        "$messageContent"
        PROMPT;
    }

    /**
     * Extracts reminder details from a message using Gemini API.
     *
     * @param string $messageContent
     * @return array|null The structured reminder details or null on failure.
     */
    public function extractReminderDetails(string $messageContent): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $prompt = $this->buildReminderExtractionPrompt($messageContent);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => $this->apiKey,
            ])->post($this->baseUrl, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $jsonText = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if ($jsonText) {
                    $decoded = json_decode($jsonText, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['message']) && isset($decoded['remind_at'])) {
                        Log::info('Successfully extracted reminder details via AI.', $decoded);
                        return $decoded;
                    }
                }
            } else {
                Log::error('Gemini API error for reminder extraction: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Gemini reminder extraction failed: ' . $e->getMessage());
        }

        return null;
    }
    /**
     * Builds the prompt for reminder detail extraction.
     *
     * @param string $messageContent
     * @return string
     */
    private function buildReminderExtractionPrompt(string $messageContent): string
    {
        $now = now()->toDateTimeString();
        $currentDate =now()->format('Y-m-d');
        $tomorrow = now()->addDay()->toDateString();
        $nextWeek = now()->addWeek()->toDateString();
        $currentYear = now()->year;

        return <<<PROMPT
        Ești un asistent expert în procesarea limbajului natural. Sarcina ta este să analizezi un text care conține un reminder și să extragi informațiile cheie într-un format JSON.

        # INFORMAȚII DE EXTRAS:
        - message: (string, obligatoriu) Textul curat al reminderului, fără informații de timp.
        - remind_at: (string, obligatoriu) Data și ora la care trebuie setat reminderul, în format "YYYY-MM-DD HH:MM:SS".
        - recurrence_rule: (string, opțional) Regula de recurență. Valori posibile: 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'.
        - recurrence_ends_at: (string, opțional) Data la care se termină recurența, în format "YYYY-MM-DD HH:MM:SS".

        # REGULI IMPORTANTE:
        1.  **Data și Ora Curente:** Consideră că data și ora curentă sunt: "$now". Toate calculele trebuie să plece de la acest moment.
        2.  **Interpretare Timp Relativ:**
            - "mâine la 10" -> "$tomorrow 10:00:00"
            - "în fiecare zi la 8" -> `recurrence_rule: 'DAILY'`. `remind_at` este prima dată de la ora 8 care urmează.
            - "în fiecare marți la 19:00" -> `recurrence_rule: 'WEEKLY'`. `remind_at` este data următoarei zile de marți.
            - "până pe 15 august" -> `recurrence_ends_at` este "$currentYear-08-15 23:59:59".
        3.  **Textul Reminderului:** Extrage doar acțiunea, fără cuvinte legate de timp. De ex., din "nu uita sa o suni pe mama maine la 12", extrage "sa o suni pe mama".

        # EXEMPLE:
        - Text: "nu uita sa o suni pe mama maine la 12"
        - Răspuns JSON:
        {
          "message": "sa o suni pe mama",
          "remind_at": "$tomorrow 12:00:00"
        }

        - Text: "plimbă câinele în fiecare zi la 8 dimineața"
        - Răspuns JSON:
        {
          "message": "plimbă câinele",
          "remind_at": "{$tomorrow} 08:00:00",
          "recurrence_rule": "DAILY"
        }

        - Text: "ia vitamina C zilnic la prânz până pe 15 august"
        - Răspuns JSON:
        {
          "message": "ia vitamina C",
          "remind_at": "{$currentDate} 12:00:00",
          "recurrence_rule": "DAILY",
          "recurrence_ends_at": "{$currentYear}-08-15 23:59:59"
        }

        # TEXT DE ANALIZAT:
        "$messageContent"

        # RĂSPUNS AȘTEPTAT:
        Returnează DOAR formatul JSON, fără nicio altă explicație.
        PROMPT;
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
