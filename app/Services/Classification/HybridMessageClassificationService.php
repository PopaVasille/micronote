<?php

namespace App\Services\Classification;

use Illuminate\Support\Facades\Log;
use App\Models\Note;

class HybridMessageClassificationService
{
    protected MessageClassificationService $regexService;
    protected GeminiClassificationService $geminiService;

    public function __construct(
        MessageClassificationService $regexService,
        GeminiClassificationService $geminiService
    ) {
        $this->regexService = $regexService;
        $this->geminiService = $geminiService;
    }

    /**
     * Clasifică mesajul folosind strategia optimă
     *
     * @param string $messageContent
     * @param bool $useAI - determină dacă să folosească AI (pentru utilizatori Plus)
     * @return string
     */
    public function classifyMessage(string $messageContent, bool $useAI = false): string
    {

Log::info('INTRU PRIN HYBRID');
        // Strategia 2: Dacă userul are dreptul la AI și serviciul e disponibil, folosește Gemini
        if ($useAI && $this->geminiService->isAvailable()) {
            $aiClassification = $this->geminiService->classifyMessage($messageContent);

            // Verifică dacă AI-ul a dat un rezultat diferit de 'simple'
            if ($aiClassification !== Note::TYPE_SIMPLE) {
                Log::info('Gemini AI classification used: ' . $aiClassification);
                return $aiClassification;
            }
        }

        // Strategia 3: Fallback la clasificarea regex completă
        $regexClassification = $this->regexService->classifyMessage($messageContent);
        Log::info('Fallback regex classification used: ' . $regexClassification);

        return $regexClassification;
    }

    /**
     * Încercări rapide de clasificare cu regex pentru cazuri evidente
     *
     * @param string $messageContent
     * @return string
     */
    private function tryQuickRegexClassification(string $messageContent): string
    {
        $content = strtolower($messageContent);

        // Cazuri foarte clare care nu necesită AI
        $quickPatterns = [
            Note::TYPE_TASK => [
                '/\b(trebuie să|nu uita|de facut|reminder|urgent)\b/u',
                '/\b(până la|deadline|până mâine|până joi)\b/u'
            ],
            Note::TYPE_SHOPING_LIST => [
                '/\b(cumpără|lista de|magazin|kaufland|lidl|carrefour)\b/u',
                '/\b(lapte|pâine|ouă|carne|legume)\b/u'
            ],
            'event' => [
                '/\b(rezervare|întâlnire|meeting|webinar)\b/u',
                '/\b(restaurant|cinema|teatru)\b/u'
            ]
        ];

        foreach ($quickPatterns as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    return $type;
                }
            }
        }

        return Note::TYPE_SIMPLE;
    }

    /**
     * Determină dacă un utilizator poate folosi AI classification
     *
     * @param $user
     * @return bool
     */
    public function canUseAI($user): bool
    {
        // Logica pentru planul Plus sau utilizatori cu drepturi speciale
        return $user && $user->plan === 'plus';
    }
}
