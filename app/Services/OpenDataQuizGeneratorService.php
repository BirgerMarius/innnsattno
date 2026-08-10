<?php

namespace App\Services;

use App\Exceptions\QuizGenerationException;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenDataQuizGeneratorService
{
    private const MIXED_CATEGORIES = [
        'norway', 'geography', 'science',
    ];

    /*
     * The source has a sitelink count, but that is not a useful enough signal
     * for an allmennquiz on its own. These small, explicit lists keep the
     * easy questions familiar and also reject retired temporary element names.
     */
    private const ESTABLISHED_ELEMENT_SYMBOLS = [
        'H', 'He', 'Li', 'Be', 'B', 'C', 'N', 'O', 'F', 'Ne', 'Na', 'Mg', 'Al', 'Si', 'P', 'S', 'Cl', 'Ar', 'K', 'Ca',
        'Sc', 'Ti', 'V', 'Cr', 'Mn', 'Fe', 'Co', 'Ni', 'Cu', 'Zn', 'Ga', 'Ge', 'As', 'Se', 'Br', 'Kr', 'Rb', 'Sr', 'Y', 'Zr',
        'Nb', 'Mo', 'Tc', 'Ru', 'Rh', 'Pd', 'Ag', 'Cd', 'In', 'Sn', 'Sb', 'Te', 'I', 'Xe', 'Cs', 'Ba', 'La', 'Ce', 'Pr', 'Nd',
        'Pm', 'Sm', 'Eu', 'Gd', 'Tb', 'Dy', 'Ho', 'Er', 'Tm', 'Yb', 'Lu', 'Hf', 'Ta', 'W', 'Re', 'Os', 'Ir', 'Pt', 'Au', 'Hg',
        'Tl', 'Pb', 'Bi', 'Po', 'At', 'Rn', 'Fr', 'Ra', 'Ac', 'Th', 'Pa', 'U', 'Np', 'Pu', 'Am', 'Cm', 'Bk', 'Cf', 'Es', 'Fm',
        'Md', 'No', 'Lr', 'Rf', 'Db', 'Sg', 'Bh', 'Hs', 'Mt', 'Ds', 'Rg', 'Cn', 'Nh', 'Fl', 'Mc', 'Lv', 'Ts', 'Og',
    ];

    private const EASY_ELEMENT_SYMBOLS = [
        'H', 'He', 'C', 'N', 'O', 'Na', 'Mg', 'Al', 'Si', 'P', 'S', 'Cl', 'K', 'Ca', 'Fe', 'Cu', 'Zn', 'Ag', 'Sn', 'I', 'Au', 'Hg', 'Pb',
    ];

    private const HARD_ELEMENT_SYMBOLS = [
        'Tc', 'Pm', 'La', 'Ce', 'Pr', 'Nd', 'Sm', 'Eu', 'Gd', 'Tb', 'Dy', 'Ho', 'Er', 'Tm', 'Yb', 'Lu', 'Hf', 'Ta', 'Re', 'Os', 'Ir', 'Pt',
        'Tl', 'Bi', 'Po', 'At', 'Rn', 'Fr', 'Ra', 'Ac', 'Th', 'Pa', 'U', 'Np', 'Pu', 'Am', 'Cm', 'Bk', 'Cf', 'Es', 'Fm', 'Md', 'No', 'Lr',
        'Rf', 'Db', 'Sg', 'Bh', 'Hs', 'Mt', 'Ds', 'Rg', 'Cn', 'Nh', 'Fl', 'Mc', 'Lv', 'Ts', 'Og',
    ];

    private const EASY_COUNTRY_IDS = [
        'Q20', 'Q34', 'Q35', 'Q33', 'Q189', 'Q183', 'Q142', 'Q29', 'Q38', 'Q145', 'Q27', 'Q30', 'Q16', 'Q96', 'Q155', 'Q414',
        'Q148', 'Q17', 'Q668', 'Q408', 'Q664', 'Q79', 'Q258', 'Q114', 'Q1033', 'Q41', 'Q36', 'Q55', 'Q31', 'Q40', 'Q39', 'Q45',
        'Q43', 'Q884', 'Q869', 'Q252', 'Q159', 'Q212', 'Q213', 'Q28', 'Q218', 'Q219', 'Q224', 'Q403', 'Q298', 'Q419', 'Q739', 'Q717', 'Q241', 'Q1028',
    ];

    private const UNSUITABLE_GEOGRAPHY_IDS = ['Q971', 'Q974', 'Q219060', 'Q865', 'Q1246', 'Q6250'];

    private const UNSUITABLE_GEOGRAPHY_LABELS = [
        'kongo', 'congo', 'kongo-brazzaville', 'kongo-kinshasa', 'staten palestina', 'palestina', 'palestine', 'taiwan', 'kosovo', 'vest-sahara', 'western sahara',
    ];

    private const EASY_NORWEGIAN_PERSON_IDS = [
        'Q926', 'Q18976', 'Q1271', 'Q2097', 'Q154912', 'Q157212', 'Q164346', 'Q120479', 'Q72292', 'Q50670', 'Q106807', 'Q120747', 'Q57287',
        'Q217344', 'Q236617', 'Q209611', 'Q233977', 'Q234380', 'Q234401', 'Q211097', 'Q233880', 'Q209254', 'Q216256', 'Q217505', 'Q215789', 'Q215813', 'Q242002', 'Q211041',
    ];

    private WikidataQuizSourceService $source;

    public function __construct(WikidataQuizSourceService $source)
    {
        $this->source = $source;
    }

    public function generate(array $settings): array
    {
        $categories = $this->sourceCategories($settings['categories']);
        $facts = [];
        $sourceErrors = 0;

        foreach ($categories as $category) {
            try {
                $facts = array_merge($facts, $this->source->facts($category, $settings['language']));
            } catch (Throwable $exception) {
                $sourceErrors++;
                Log::warning('Could not fetch quiz facts from Wikidata.', [
                    'category' => $category,
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if (!$facts && $sourceErrors) {
            throw new QuizGenerationException('Den åpne datakilden er midlertidig utilgjengelig. Prøv igjen senere.');
        }

        $facts = $this->forDifficulty($facts, $settings['difficulty']);
        $optionFacts = $facts;
        $facts = $this->balancedFacts($facts, $categories, $settings['question_count']);

        $questions = [];
        $used = [];

        foreach ($facts as $fact) {
            $identity = mb_strtolower($fact['fact_type'].'|'.$fact['subject']);

            if (isset($used[$identity])) {
                continue;
            }

            $options = [];
            $correctOption = '';

            if ($settings['question_type'] === 'multiple_choice') {
                $options = $this->options($fact, $optionFacts);

                if (count($options) !== 4) {
                    continue;
                }

                shuffle($options);
                $correctIndex = array_search($fact['answer'], $options, true);
                $correctOption = chr(ord('A') + $correctIndex);
            }

            $used[$identity] = true;
            $questions[] = [
                'question' => $this->questionText($fact, $settings['language']),
                'category' => $this->categoryLabel($fact['category'], $settings['language']),
                'correct_answer' => $fact['answer'],
                'options' => $options,
                'correct_option' => $correctOption,
                'explanation' => '',
                'uncertain' => false,
                'source' => $fact['source'],
                'source_url' => $fact['source_url'],
            ];

            if (count($questions) === $settings['question_count']) {
                break;
            }
        }

        if (count($questions) < $settings['question_count']) {
            throw new QuizGenerationException(
                'Fant bare '.count($questions).' egnede spørsmål for valgene dine. Prøv flere kategorier eller en annen vanskelighetsgrad.'
            );
        }

        return [
            'title' => $settings['language'] === 'en' ? 'Open data quiz' : 'Quiz fra åpne data',
            'questions' => $questions,
        ];
    }

    private function sourceCategories(array $selected): array
    {
        if (in_array('mixed', $selected, true)) {
            $categories = self::MIXED_CATEGORIES;
            shuffle($categories);

            return array_slice($categories, 0, 3);
        }

        return array_values($selected);
    }

    private function forDifficulty(array $facts, string $difficulty): array
    {
        $facts = array_values(array_filter($facts, fn (array $fact) => $this->isSafeFact($fact)));

        $byCategory = [];
        foreach ($facts as $fact) {
            $byCategory[$fact['category']][] = $fact;
        }

        $filtered = [];
        foreach ($byCategory as $category => $categoryFacts) {
            usort($categoryFacts, fn (array $a, array $b) => $b['popularity'] <=> $a['popularity']);
            $filtered = array_merge($filtered, $this->factsForDifficulty($categoryFacts, $difficulty, $category));
        }

        return $filtered;
    }

    private function factsForDifficulty(array $facts, string $difficulty, string $category): array
    {
        $facts = array_values(array_filter($facts, function (array $fact) use ($difficulty): bool {
            if ($fact['fact_type'] === 'element_symbol') {
                if (!in_array($fact['answer'], self::ESTABLISHED_ELEMENT_SYMBOLS, true)) {
                    return false;
                }

                if ($difficulty === 'easy') {
                    return in_array($fact['answer'], self::EASY_ELEMENT_SYMBOLS, true);
                }

                if ($difficulty === 'medium') {
                    return !in_array($fact['answer'], self::HARD_ELEMENT_SYMBOLS, true);
                }
            }

            if ($fact['fact_type'] === 'capital' && $difficulty === 'easy') {
                return in_array($this->subjectId($fact), self::EASY_COUNTRY_IDS, true);
            }

            return true;
        }));

        if ($difficulty === 'mixed' || count($facts) < 10) {
            return $facts;
        }

        if ($difficulty === 'easy') {
            if ($category === 'norway') {
                $knownPeople = $this->knownNorwegianFacts($facts);

                if (count($knownPeople) >= 10) {
                    return $knownPeople;
                }
            }

            return array_values(array_filter($facts, fn (array $fact) => $this->isEasyNonTechnicalFact($fact)));
        }

        if ($difficulty === 'hard') {
            return array_slice($facts, (int) floor(count($facts) * .35));
        }

        if ($category === 'norway') {
            $knownPeople = $this->knownNorwegianFacts($facts);

            return count($knownPeople) >= 10
                ? $knownPeople
                : array_values(array_filter($facts, fn (array $fact) => $fact['popularity'] >= 50));
        }

        return array_slice($facts, 0, max(1, (int) ceil(count($facts) * .9)));
    }

    private function knownNorwegianFacts(array $facts): array
    {
        return array_values(array_filter($facts, fn (array $fact) => in_array($this->subjectId($fact), self::EASY_NORWEGIAN_PERSON_IDS, true)));
    }

    private function isSafeFact(array $fact): bool
    {
        if ($fact['fact_type'] !== 'capital') {
            return true;
        }

        return !in_array($this->subjectId($fact), self::UNSUITABLE_GEOGRAPHY_IDS, true)
            && !in_array(mb_strtolower(trim($fact['subject'])), self::UNSUITABLE_GEOGRAPHY_LABELS, true);
    }

    private function isEasyNonTechnicalFact(array $fact): bool
    {
        if (in_array($fact['fact_type'], ['element_symbol', 'capital'], true)) {
            return true;
        }

        return $fact['popularity'] >= 50;
    }

    private function balancedFacts(array $facts, array $categories, int $count): array
    {
        $byCategory = array_fill_keys($categories, []);
        foreach ($facts as $fact) {
            if (array_key_exists($fact['category'], $byCategory)) {
                $byCategory[$fact['category']][] = $fact;
            }
        }

        foreach ($byCategory as &$categoryFacts) {
            $categoryFacts = $this->interleaveFactTypes($categoryFacts);
        }
        unset($categoryFacts);

        $selected = [];
        while (count($selected) < $count) {
            $added = false;
            foreach ($categories as $category) {
                if (count($selected) === $count) {
                    break;
                }

                $fact = array_shift($byCategory[$category]);
                if ($fact !== null) {
                    $selected[] = $fact;
                    $added = true;
                }
            }

            if (!$added) {
                break;
            }
        }

        return $selected;
    }

    private function interleaveFactTypes(array $facts): array
    {
        $byType = [];
        $seen = [];
        foreach ($facts as $fact) {
            $identity = mb_strtolower($fact['fact_type'].'|'.$fact['subject']);
            if (isset($seen[$identity])) {
                continue;
            }
            $seen[$identity] = true;
            $byType[$fact['fact_type']][] = $fact;
        }

        foreach ($byType as &$typeFacts) {
            shuffle($typeFacts);
        }
        unset($typeFacts);

        $interleaved = [];
        while ($byType) {
            foreach (array_keys($byType) as $type) {
                $fact = array_shift($byType[$type]);
                if ($fact !== null) {
                    $interleaved[] = $fact;
                }
                if (!$byType[$type]) {
                    unset($byType[$type]);
                }
            }
        }

        return $interleaved;
    }

    private function subjectId(array $fact): string
    {
        if (isset($fact['subject_id'])) {
            return (string) $fact['subject_id'];
        }

        preg_match('/(Q\d+)$/', (string) ($fact['source_url'] ?? ''), $matches);

        return $matches[1] ?? '';
    }

    private function options(array $correct, array $facts): array
    {
        $answers = [];

        foreach ($facts as $fact) {
            if ($fact['fact_type'] === $correct['fact_type']
                && $fact['answer'] !== $correct['answer']
                && $this->isPlausibleDistractor($correct, $fact)) {
                $answers[mb_strtolower($fact['answer'])] = $fact['answer'];
            }
        }

        $answers = array_values($answers);
        shuffle($answers);

        return count($answers) >= 3
            ? array_merge([$correct['answer']], array_slice($answers, 0, 3))
            : [];
    }

    private function isPlausibleDistractor(array $correct, array $candidate): bool
    {
        if ($correct['fact_type'] !== 'birth_year') {
            return true;
        }

        return preg_match('/^\d{4}$/', $correct['answer'])
            && preg_match('/^\d{4}$/', $candidate['answer'])
            && abs((int) $correct['answer'] - (int) $candidate['answer']) <= 25;
    }

    private function questionText(array $fact, string $language): string
    {
        $templates = $this->templates()[$language === 'en' ? 'en' : 'no'][$fact['fact_type']] ?? [];

        if (!$templates) {
            throw new QuizGenerationException('Datakilden returnerte en ukjent faktatype.');
        }

        return sprintf($templates[array_rand($templates)], $fact['subject']);
    }

    private function templates(): array
    {
        return [
            'no' => [
                'birth_year' => ['I hvilket år ble %s født?', 'Hva er fødselsåret til %s?'],
                'occupation' => ['Hva er yrket til %s?', 'Hva arbeider %s som?'],
                'event_year' => ['I hvilket år fant %s sted?', 'Hvilket år forbindes med %s?'],
                'capital' => ['Hva er hovedstaden i %s?', 'Hvilken by er hovedstad i %s?'],
                'continent' => ['Hvilken verdensdel ligger %s i?', '%s ligger i hvilken verdensdel?'],
                'place_country' => ['I hvilket land ligger %s?', 'Hvilket land tilhører byen %s?'],
                'element_symbol' => ['Hva er det kjemiske symbolet for %s?', 'Hvilket grunnstoff har navnet %s? Oppgi symbolet.'],
                'athlete_sport' => ['Hvilken idrett er %s kjent for?', '%s forbindes med hvilken idrett?'],
                'film_director' => ['Hvem regisserte filmen %s?', 'Hva heter regissøren av %s?'],
                'composer' => ['Hvem komponerte %s?', 'Hva heter komponisten bak %s?'],
                'author' => ['Hvem skrev %s?', 'Hva heter forfatteren av %s?'],
                'software_developer' => ['Hvem utviklet %s?', 'Hvilken virksomhet står bak %s?'],
                'animal_genus' => ['Hvilken slekt tilhører %s?', '%s hører til hvilken biologisk slekt?'],
            ],
            'en' => [
                'birth_year' => ['In which year was %s born?', 'What is the birth year of %s?'],
                'occupation' => ['What is the occupation of %s?', 'What does %s do?'],
                'event_year' => ['In which year did %s take place?', 'Which year is associated with %s?'],
                'capital' => ['What is the capital of %s?', 'Which city is the capital of %s?'],
                'continent' => ['On which continent is %s located?', '%s is located on which continent?'],
                'place_country' => ['In which country is %s located?', 'Which country is the city of %s in?'],
                'element_symbol' => ['What is the chemical symbol for %s?', 'Which symbol represents the element %s?'],
                'athlete_sport' => ['Which sport is %s known for?', '%s is associated with which sport?'],
                'film_director' => ['Who directed the film %s?', 'Who is the director of %s?'],
                'composer' => ['Who composed %s?', 'Who is the composer behind %s?'],
                'author' => ['Who wrote %s?', 'Who is the author of %s?'],
                'software_developer' => ['Who developed %s?', 'Which organisation developed %s?'],
                'animal_genus' => ['Which genus does %s belong to?', '%s belongs to which biological genus?'],
            ],
        ];
    }

    private function categoryLabel(string $category, string $language): string
    {
        $labels = [
            'no' => ['norway' => 'Norge', 'history' => 'Historie', 'geography' => 'Geografi', 'science' => 'Natur og vitenskap', 'sport' => 'Sport', 'film_tv' => 'Film og TV', 'music' => 'Musikk', 'literature_language' => 'Litteratur og språk', 'technology' => 'Teknologi', 'animals_nature' => 'Dyr og natur'],
            'en' => ['norway' => 'Norway', 'history' => 'History', 'geography' => 'Geography', 'science' => 'Nature and science', 'sport' => 'Sport', 'film_tv' => 'Film and TV', 'music' => 'Music', 'literature_language' => 'Literature and language', 'technology' => 'Technology', 'animals_nature' => 'Animals and nature'],
        ];

        return $labels[$language === 'en' ? 'en' : 'no'][$category] ?? $category;
    }
}
