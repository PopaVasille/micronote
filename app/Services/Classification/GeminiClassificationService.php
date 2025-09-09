<?php

namespace App\Services\Classification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Note;
use Illuminate\Support\Str;

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
        // Check rate limits before making API call
        if (!$this->isWithinRateLimits()) {
            return $this->fallbackToRegexClassification($messageContent);
        }

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
                // Increment rate limiters only after successful call
                $this->incrementRateLimiters();
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
            - task: O acțiune sau o sarcină generală, fără un timp anume. Ceva ce trebuie "făcut". Ex: "repară gardul".
            - reminder: O acțiune personală pe care TU trebuie să o faci la un moment specific. O "alarmă" pentru o acțiune. Dacă mesajul conține o acțiune ȘI un timp, este aproape întotdeauna un REMINDER. Ex: "sun-o pe mama la 17:00".
            - event: O întâmplare programată, care implică o locație sau alte persoane (întâlnire, rezervare, concert). Ceva la care participi. Ex: "întâlnire la birou la 10".
            - idea: Un concept, un gând sau o sugestie creativă.
            - shopping_list: O listă de produse sau articole de cumpărat.
            - contact: Informații despre o persoană (nume, telefon, email).
            - recipe: Instrucțiuni de gătit, ingrediente pentru o rețetă.
            - bookmark: Un link web (URL) care trebuie salvat.
            - measurement: O valoare numerică cu o unitate de măsură (ex: cm, kg, m²).
            - simple: Orice mesaj care nu se încadrează clar în categoriile de mai sus.

            # EXEMPLE DE CLASIFICARE CORECTĂ:
            - Mesaj: "trebuie sa termin raportul pana la finalul saptamanii" -> Răspuns: task
            - Mesaj: "Comandă beton mâine la ora 19" -> Răspuns: reminder
            - Mesaj: "Nu uita sa o suni pe mama maine la 12." -> Răspuns: reminder
            - Mesaj: "cumparaturi: lapte, paine, oua de la lidl" -> Răspuns: shopping_list
            - Mesaj: "ar fi misto sa facem un podcast despre istorie" -> Răspuns: idea
            - Mesaj: "Rezervare la Trattoria vineri la 19:30 pentru 4 persoane" -> Răspuns: event
            - Mesaj: "sedinta la birou maine la ora 11" -> Răspuns: event

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

        // Check rate limits before making API call
        if (!$this->isWithinRateLimits()) {
            Log::info('Gemini API rate limit exceeded - skipping shopping list extraction');
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
                // Increment rate limiters only after successful call
                $this->incrementRateLimiters();
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
        1. Ignoră orice text care nu pare a fi un item de cumpărături (cum ar fi nume de persoane, verbe, etc.).
        2. Tratează cuvintele de legătură precum 'și', 'iar', 'cu', 'plus' ca separatori de itemi.
        3. Ignoră expresii precum "pentru [nume persoană]" - extrage doar itemele de cumpărat.
        4. Asigură-te că un item extras NU începe cu cuvinte de legătură.
        5. Separă itemele individuale chiar dacă sunt grupate în același segment de text.

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
        Text: "iau bere lapte si pentru mara sa iau oua"
        Răspuns JSON:
        {
          "items": [
            { "text": "bere", "completed": false },
            { "text": "lapte", "completed": false },
            { "text": "oua", "completed": false }
          ]
        }

        Exemplu 3:
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

        // Check rate limits before making API call
        if (!$this->isWithinRateLimits()) {
            Log::info('Gemini API rate limit exceeded - skipping reminder extraction');
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
                // Increment rate limiters only after successful call
                $this->incrementRateLimiters();
                $result = $response->json();
                $jsonText = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
Log::info('in extractia de informatii'.json_encode($jsonText));
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
        $tomorrow = now()->addDay()->toDateString();
        $today = now()->toDateString();

        return <<<PROMPT
        Ești un asistent expert în extragerea de date din text. Sarcina ta este să analizezi un text și să extragi detaliile unui reminder într-un format JSON.

        # INFORMAȚII DE EXTRAS:
        - `message`: (string, obligatoriu) Textul curat al acțiunii/evenimentului, FĂRĂ nicio informație despre timp.
        - `remind_at`: (string, obligatoriu) Data și ora la care trebuie setat reminderul, în format `YYYY-MM-DD HH:MM:SS`.

        # REGULI DE INTERPRETARE TEMPORALĂ:
        1. **Data de Referință:** Punctul de plecare pentru orice calcul este momentul curent: `$now`.
        2. **Regula de Aur:** Dacă mesajul conține o referință temporală explicită (ex: "mâine la 19", "marți la 10"), această referință determină EXACT când să se declanșeze reminderul.
        3. **Ore Implicite:** Dacă nu există oră specificată, folosește ora `09:00:00`.
        4. **Interpretarea temporală:**
           - "mâine" = `$tomorrow`
           - "azi" = `$today`
           - "la ora X" = ora X în ziua specificată
           - "marți", "miercuri", etc. = următoarea zi specificată

        # EXEMPLE:

        ## Exemplu 1: Referință temporală explicită
        - Text: "trebuie sa duc cainele la veterinar maine la 19"
        - Gândire: "mâine la 19" înseamnă mâine la ora 19:00
        - Răspuns JSON:
        {
          "message": "să duc cainele la veterinar",
          "remind_at": "$tomorrow 19:00:00"
        }

        ## Exemplu 2: Doar ziua specificată
        - Text: "nu uita de întâlnirea cu mama maine"
        - Gândire: "mâine" fără oră = mâine la 09:00
        - Răspuns JSON:
        {
          "message": "întâlnirea cu mama",
          "remind_at": "$tomorrow 09:00:00"
        }

        ## Exemplu 3: Ora în ziua curentă
        - Text: "sună-o pe mama la 17:30"
        - Gândire: Ora specificată în ziua curentă
        - Răspuns JSON:
        {
          "message": "să o suni pe mama",
          "remind_at": "$today 17:30:00"
        }

        # TEXT DE ANALIZAT:
        `$messageContent`

        # RĂSPUNS AȘTEPTAT:
        Returnează DOAR formatul JSON valid, fără nicio altă explicație.
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
     * Generates an AI-powered title for a note based on its content.
     *
     * @param string $messageContent
     * @param string|null $noteType
     * @return string|null The generated title or null on failure
     */
    public function generateNoteTitle(string $messageContent, ?string $noteType = null): ?string
    {
        if (!$this->isAvailable()) {
            return null;
        }

        // Check rate limits before making API call
        if (!$this->isWithinRateLimits()) {
            Log::info('Gemini API rate limit exceeded - skipping title generation');
            return null;
        }

        $prompt = $this->buildTitleGenerationPrompt($messageContent, $noteType);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => $this->apiKey,
            ])->post($this->baseUrl, [
                'contents' => [['parts' => [['text' => $prompt]]]],
            ]);

            if ($response->successful()) {
                // Increment rate limiters only after successful call
                $this->incrementRateLimiters();
                $result = $response->json();
                $title = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if ($title) {
                    $cleanTitle = trim($title, '"\'');
                    $cleanTitle = Str::limit($cleanTitle, 50);
                    $cleanTitle = trim($cleanTitle);
                    Log::info('Successfully generated AI title.', ['original' => $messageContent, 'title' => $cleanTitle]);
                    return $cleanTitle;
                }
            } else {
                Log::error('Gemini API error for title generation: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Gemini title generation failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Builds the prompt for AI title generation.
     *
     * @param string $messageContent
     * @param string|null $noteType
     * @return string
     */
    private function buildTitleGenerationPrompt(string $messageContent, ?string $noteType = null): string
    {
        $typeContext = '';
        if ($noteType) {
            $typeContext = match($noteType) {
                Note::TYPE_TASK => 'Aceasta este o sarcină.',
                Note::TYPE_REMINDER => 'Aceasta este o notificare/reminder.',
                Note::TYPE_SHOPING_LIST => 'Aceasta este o listă de cumpărături.',
                Note::TYPE_IDEA => 'Aceasta este o idee.',
                Note::TYPE_EVENT => 'Aceasta este un eveniment.',
                Note::TYPE_RECIPE => 'Aceasta este o rețetă.',
                Note::TYPE_CONTACT => 'Acestea sunt informații de contact.',
                default => ''
            };
        }

        return <<<PROMPT
        Ești un asistent expert în generarea de titluri scurte și descriptive pentru notițe.
        Sarcina ta este să creezi un titlu concis (maxim 6-8 cuvinte) pentru următorul conținut.

        $typeContext

        Reguli pentru titlu:
        1. Maxim 6-8 cuvinte
        2. Să fie descriptiv și să rezume esența conținutului
        3. Să nu includă cuvinte de legătură inutile
        4. Să înceapă cu literă mare
        5. Să nu includă ghilimele sau punctuație la sfârșitul

        Exemple:
        - Conținut: "nu uita sa o suni pe mama maine la 12" -> Titlu: "Sună mama mâine la 12"
        - Conținut: "iau bere lapte si oua" -> Titlu: "Listă cumpărături: bere, lapte, ouă"
        - Conținut: "trebuie sa termin raportul pana vineri" -> Titlu: "Termină raportul până vineri"
        - Conținut: "am o idee pentru aplicatia mobila" -> Titlu: "Idee pentru aplicația mobilă"

        Conținut de analizat: "$messageContent"

        Răspunde DOAR cu titlul generat, fără alte explicații:
        PROMPT;
    }

    /**
     * Extracts multiple actions of different types from a single message (Premium feature)
     *
     * @param string $messageContent
     * @return array|null The structured actions array or null on failure
     */
    public function extractMultipleActions(string $messageContent): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        // Check rate limits before making API call
        if (!$this->isWithinRateLimits()) {
            Log::info('Gemini API rate limit exceeded - skipping multi-action extraction');
            return null;
        }

        $prompt = $this->buildMultipleActionsPrompt($messageContent);

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
                // Increment rate limiters only after successful call
                $this->incrementRateLimiters();
                $result = $response->json();
                $jsonText = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if ($jsonText) {
                    $decoded = json_decode($jsonText, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        Log::info('Successfully extracted multiple actions via AI.', $decoded);
                        return $decoded;
                    }
                }
            } else {
                Log::error('Gemini API error for multi-action extraction: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Gemini multi-action extraction failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Builds the prompt for multiple actions extraction (Premium feature)
     *
     * @param string $messageContent
     * @return string
     */
    private function buildMultipleActionsPrompt(string $messageContent): string
    {
        $now = now()->toDateTimeString();
        $currentDate = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->toDateString();

        return <<<PROMPT
        Ești un asistent expert în procesarea limbajului natural. Sarcina ta este să analizezi un text și să extragi TOATE acțiunile de TOATE tipurile într-un format JSON structurat.

        # REGULI DE CLASIFICARE (FOARTE IMPORTANT):
        - REGULA DE AUR: Dacă o acțiune conține o referință temporală (ex: "mâine", "marți", "la ora 10", "peste 3 zile", "anul viitor"), este aproape întotdeauna un REMINDER.
        - Un TASK este doar o acțiune generală, fără nicio referință temporală. Ex: "curăță garajul", "scrie articolul".
        - Dacă o acțiune este un REMINDER dar NU are o oră specifică (ex: "mâine", "marți"), setează ora implicită la 07:00:00.

        # TIPURI DE ACȚIUNI ȘI FORMATUL LOR:
        - reminders: O acțiune personală cu timp specific.
          - Format: `[{"message": "textul curat", "remind_at": "YYYY-MM-DD HH:MM:SS"}]`
        - tasks: O sarcină generală fără timp.
          - Format: `[{"title": "Titlul task-ului (CU MAJUSCULĂ)", "content": "descrierea"}]`
        - shopping_list: O listă de cumpărături.
          - Format: `{"title": "titlu", "items": [{"text": "item", "completed": false}]}`
        - ideas: Concepte sau gânduri.
          - Format: `[{"title": "titlu idee", "content": "descriere"}]`
        - events: Întâmplări programate.
          - Format: `[{"title": "nume eveniment", "date": "YYYY-MM-DD HH:MM:SS", "location": "locație"}]`
        - contacts: Informații despre persoane.
          - Format: `[{"name": "nume", "phone": "telefon", "email": "email"}]`
        - recipes: Instrucțiuni de gătit.
          - Format: `[{"title": "Nume rețetă", "ingredients": ["ingredient 1"], "steps": "Pasul 1..."}]`
        - bookmarks: Un link web (URL).
          - Format: `[{"url": "https://...", "title": "Titlu opțional"}]`
        - measurements: O valoare numerică cu o unitate de măsură.
          - Format: `[{"subject": "Ce se măsoară", "value": 180, "unit": "cm"}]`
        - simple: Orice mesaj care nu se încadrează clar în categoriile de mai sus.
          - Format: `[{"content": "conținutul notei"}]`

        # INFORMAȚII DE CONTEXT:
        - Data și ora curentă este: "$now". Folosește-o pentru a calcula datele relative.
        - "Mâine" este "$tomorrow".

        # EXEMPLE DE PROCESARE CORECTĂ:

        ## Exemplu 1:
        Mesaj: "trebuie sa dau comanda de aspirator maine si sa cumpar paine si oua"
        Răspuns JSON:
        {
            "reminders": [
                {
                    "message": "să dau comanda de aspirator",
                    "remind_at": "$tomorrow 07:00:00"
                }
            ],
            "shopping_list": {
                "title": "Lista de cumpărături",
                "items": [
                    { "text": "paine", "completed": false },
                    { "text": "oua", "completed": false }
                ]
            }
        }

        ## Exemplu 2:
        Mesaj: "trebuie sa dau comanda de beton pentru maine pentru iasi si marti pentru bacau"
        Răspuns JSON:
        {
            "reminders": [
                {
                    "message": "să dau comandă de beton pentru Iași",
                    "remind_at": "$tomorrow 07:00:00"
                },
                {
                    "message": "să dau comandă de beton pentru Bacău",
                    "remind_at": "YYYY-MM-DD 07:00:00"
                }
            ]
        }

        ## Exemplu 3:
        Mesaj: "termină raportul și nu uita să o suni pe mama la 17:30"
        Răspuns JSON:
        {
            "tasks": [
                {
                    "title": "Termină raportul",
                    "content": "termină raportul"
                }
            ],
            "reminders": [
                {
                    "message": "să o suni pe mama",
                    "remind_at": "$currentDate 17:30:00"
                }
            ]
        }

        # REGULI SUPLIMENTARE PENTRU JSON:
        1. Analizează întregul text și identifică TOATE acțiunile posibile.
        2. Pentru `tasks`, asigură-te că `title` începe întotdeauna cu majusculă.
        3. Nu include chei goale în JSON. Dacă nu găsești un anumit tip de acțiune, omite complet cheia respectivă.
        4. Asigură-te că JSON-ul returnat este întotdeauna valid.

        # TEXT DE ANALIZAT:
        "$messageContent"

        # RĂSPUNS AȘTEPTAT:
        Returnează DOAR formatul JSON valid, fără nicio altă explicație.
        PROMPT;
    }

    /**
     * Performs the initial triage of a message to identify all potential actions within it.
     *
     * @param string $messageContent
     * @return array|null The structured array of identified action types and their raw text.
     */
    public function triageMultipleActions(string $messageContent): ?array
    {
        if (!$this->isAvailable() || !$this->isWithinRateLimits()) {
            Log::warning('Gemini API not available or rate limit exceeded for multi-action triage.');
            return null;
        }

        $prompt = $this->buildTriagePrompt($messageContent);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => $this->apiKey,
            ])->post($this->baseUrl, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['response_mime_type' => 'application/json'],
            ]);

            if ($response->successful()) {
                $this->incrementRateLimiters();
                $result = $response->json();
                $jsonText = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if ($jsonText) {
                    $decoded = json_decode($jsonText, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['actions'])) {
                        Log::info('Successfully triaged multiple actions via AI.', $decoded);
                        return $decoded['actions'];
                    }
                }
            } else {
                Log::error('Gemini API error for multi-action triage: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Gemini multi-action triage failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Builds the prompt for the multi-action triage step.
     *
     * @param string $messageContent
     * @return string
     */
    private function buildTriagePrompt(string $messageContent): string
    {
        return <<<PROMPT
        Ești un asistent expert în analiza textului. Sarcina ta este să identifici și să separi TOATE acțiunile distincte dintr-un mesaj. Nu trebuie să le procesezi, doar să le identifici.

        # TIPURI DE ACȚIUNI DE IDENTIFICAT:
        - reminder: O acțiune cu o referință temporală (ex: mâine, la ora 5, marți).
        - task: O sarcină generală fără timp.
        - shopping_list: O listă de cumpărături.
        - idea: O idee sau un concept.
        - event: Un eveniment programat.
        - contact: Informații de contact.
        - recipe: O rețetă.
        - bookmark: Un link/URL.
        - measurement: O măsurătoare.
        - simple: Orice altceva.

        # REGULI:
        1.  Returnează un obiect JSON care conține o singură cheie: "actions".
        2.  Valoarea pentru "actions" trebuie să fie o listă (array) de obiecte.
        3.  Fiecare obiect din listă trebuie să aibă două chei: "type" (unul din tipurile de mai sus) și "text" (textul original corespunzător acelei acțiuni).
        4.  Combină itemii unei liste de cumpărături într-o singură acțiune de tip `shopping_list`.
        5.  **REGULĂ IMPORTANTĂ:** Dacă o acțiune se repetă cu contexte diferite (ex: locații, date), creează acțiuni separate, dar asigură-te că fiecare acțiune nouă conține și verbul/substantivul principal din acțiunea inițială pentru a păstra contextul complet.

        # EXEMPLE:

        ## Exemplu 1:
        Mesaj: "trebuie sa dau comanda de aspirator maine si sa cumpar paine si oua"
        Răspuns JSON:
        {
            "actions": [
                { "type": "reminder", "text": "trebuie sa dau comanda de aspirator maine" },
                { "type": "shopping_list", "text": "sa cumpar paine si oua" }
            ]
        }

        ## Exemplu 2:
        Mesaj: "termină raportul și nu uita să o suni pe mama la 17:30"
        Răspuns JSON:
        {
            "actions": [
                { "type": "task", "text": "termină raportul" },
                { "type": "reminder", "text": "nu uita să o suni pe mama la 17:30" }
            ]
        }

        ## Exemplu 3 (Păstrarea Contextului):
        Mesaj: "trebuie sa dau comanda de manusi maine pentru buzau si miercuri pentru botosani"
        Răspuns JSON:
        {
            "actions": [
                { "type": "reminder", "text": "trebuie sa dau comanda de manusi maine pentru buzau" },
                { "type": "reminder", "text": "trebuie sa dau comanda de manusi miercuri pentru botosani" }
            ]
        }

        # TEXT DE ANALIZAT:
        "$messageContent"

        # RĂSPUNS AȘTEPTAT:
        Returnează DOAR formatul JSON valid, fără nicio altă explicație.
        PROMPT;
    }

    /**
     * Verifică dacă serviciul Gemini e disponibil
     *


    /**
     * Verifică dacă serviciul Gemini e disponibil
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Check if Gemini API is within rate limits before making a call
     *
     * @return bool
     */
    protected function isWithinRateLimits(): bool
    {
        $rpmLimit = config('services.gemini.rpm_limit');
        $dailyLimit= config('services.gemini.daily_limit');


        // Check both RPM and daily limits
        $withinRpmLimit = !RateLimiter::tooManyAttempts('gemini-api-rpm', $rpmLimit);
        $withinDailyLimit = !RateLimiter::tooManyAttempts('gemini-api-daily', $dailyLimit);

        if (!$withinRpmLimit) {
            Log::warning('Gemini API: RPM limit (30/minute) exceeded - using fallback');
            return false;
        }

        if (!$withinDailyLimit) {
            Log::warning('Gemini API: Daily limit (1500/day) exceeded - using fallback');
            return false;
        }

        return true;
    }

    /**
     * Increment rate limiting counters after successful API call
     */
    protected function incrementRateLimiters(): void
    {
        RateLimiter::hit('gemini-api-rpm');
        RateLimiter::hit('gemini-api-daily');
    }
}
