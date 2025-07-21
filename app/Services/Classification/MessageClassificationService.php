<?php

namespace App\Services\Classification;

use App\Models\Note;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

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

            // Dacă regexPatterns este null, folosim valori implicite CU delimitatori corecți
            if ($regexPatterns === null) {
                Log::warning('Nu s-au găsit pattern-uri regex în setările sistemului. Se folosesc valori implicite.');
                $patterns = [
                    'task' => '/\b(task|todo|reminder|de (facut|făcut|facut)|trebuie (sa|să|s[aă])|nu uita|aminteste|amintește)\b/i',
                    'idea' => '/\b(idee|idea|concept|brainstorm|gandesc la|gândesc la|ar fi (bine|misto))\b/i',
                    'shopping' => '/\b(cumpără|cumparaturi|cumpărături|lista|magazin)\b/i',
                ];
            } else {
                // Decodăm JSON-ul
                $decodedPatterns = json_decode($regexPatterns, true);

                if (!is_array($decodedPatterns)) {
                    throw new \Exception('Formatarea pattern-urilor regex este incorectă.');
                }

                $patterns = [];
                foreach ($decodedPatterns as $key => $pattern) {
                    // VERIFICĂM dacă pattern-ul are deja delimitatori
                    if ($this->hasDelimiters($pattern)) {
                        // Dacă are deja delimitatori, îl folosim direct
                        $patterns[$key] = $pattern . 'i'; // Adăugăm doar flag-ul case-insensitive
                    } else {
                        // Dacă nu are delimitatori, construim unul sigur
                        $patterns[$key] = $this->buildSafePattern($pattern);
                    }
                }
            }

            // Verificăm fiecare pattern
            foreach ($patterns as $type => $pattern) {
                Log::debug("Testăm pattern-ul pentru '$type': $pattern");

                if (preg_match($pattern, $messageContent)) {
                    Log::info("Găsit match pentru tipul: $type");

                    switch ($type) {
                        case 'task':
                            return Note::TYPE_TASK;
                        case 'idea':
                            return Note::TYPE_IDEA;
                        case 'shopping':
                            return Note::TYPE_SHOPING_LIST;
                        case 'reminder':
                            return Note::TYPE_REMINDER;
                    }
                }
            }

            return Note::TYPE_SIMPLE;

        } catch (\Exception $e) {
            Log::error('Eroare la clasificarea mesajului: ' . $e->getMessage());
            return Note::TYPE_SIMPLE;
        }
    }

    /**
     * Verifică dacă un pattern are deja delimitatori
     *
     * @param string $pattern
     * @return bool
     */
    private function hasDelimiters(string $pattern): bool
    {
        // Verificăm dacă începe și se termină cu același caracter delimitator
        if (strlen($pattern) < 2) {
            return false;
        }

        $firstChar = $pattern[0];
        $lastChar = $pattern[strlen($pattern) - 1];

        // Delimitatorii obișnuiți în PHP regex
        $validDelimiters = ['/', '#', '~', '@', '|', '!'];

        return in_array($firstChar, $validDelimiters) && $firstChar === $lastChar;
    }

    /**
     * Escapează caracterele speciale din pattern și alege delimitatorul potrivit
     *
     * @param string $pattern
     * @return string
     */
    private function buildSafePattern(string $pattern): string
    {
        // Dacă conține slash-uri, folosim # ca delimitator
        if (strpos($pattern, '/') !== false) {
            return '#' . $pattern . '#i';
        }

        // Dacă conține #, folosim ~ ca delimitator
        if (strpos($pattern, '#') !== false) {
            return '~' . $pattern . '~i';
        }

        // În rest, folosim / standard
        return '/' . $pattern . '/i';
    }
}
