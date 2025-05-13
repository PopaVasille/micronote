<?php

namespace App\Services\Classification;

use App\Models\Note;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;
use Normalizer;

class MessageClassificationService
{
    /**
     * Clasifică un mesaj folosind regex pentru a determina tipul (task, idee, cumpărături)
     *
     * @param string $messageContent
     * @return string Tipul de notiță detectat ('task', 'idea', 'shopping_list', 'simple')
     */
    public function classifyMessage(string $messageContent): string
    {
        try {
            // Obține pattern-urile regex din setările sistemului
            $regexPatterns = SystemSetting::where('key_name', 'regex_patterns')
                ->value('json_value');

            //$messageContent = $this->normalizeString($messageContent);

            // Dacă regexPatterns este null sau nu poate fi decodat, folosim valori implicite
            if ($regexPatterns === null) {
                Log::warning('Nu s-au găsit pattern-uri regex în setările sistemului. Se folosesc valori implicite.');
                $patterns = [
                    'task' => '#\b(task|todo|reminder|de (facut|făcut|facut)|trebuie (sa|să|s[aă])|nu uita|aminteste|amintește)\b#i',
                    'idea' => '/\b(idee|idea|concept|brainstorm|gandesc la|gândesc la|ar fi (bine|misto))\b/i',
                    'shopping' => '/\b(cumpără|cumparaturi|cumpărături|lista|magazin)\b/i',
                ];
            } else {
                // Decodăm JSON-ul și convertim în formatul potrivit pentru regex
                $decodedPatterns = json_decode($regexPatterns, true);

                if (!is_array($decodedPatterns)) {
                    throw new \Exception('Formatarea pattern-urilor regex este incorectă.');
                }

                $patterns = [];
                foreach ($decodedPatterns as $key => $pattern) {
                    // Convertim formatul din JSON în formatul cerut de preg_match
                    $patterns[$key] = '/' . $pattern . '/iu';
                }
            }

            // Verificăm fiecare pattern
            foreach ($patterns as $type => $pattern) {
                if (preg_match($pattern, $messageContent)) {
                    switch ($type) {
                        case 'task':
                            return Note::TYPE_TASK;
                        case 'idea':
                            return Note::TYPE_IDEA;
                        case 'shopping':
                            return Note::TYPE_SHOPING_LIST;
                    }
                }
            }

            return Note::TYPE_SIMPLE;

        } catch (\Exception $e) {
            Log::error('Eroare la clasificarea mesajului: ' . $e->getMessage());
            return Note::TYPE_SIMPLE;
        }
    }
//    private function normalizeString(string $str): string
//    {
//        // Normalizăm la forma NFKC (compatibilitate compusă)
//        return Normalizer::normalize($str, Normalizer::FORM_KC);
//    }

}
