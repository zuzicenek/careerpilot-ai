
<?php

function escape(string $value): string {
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$job = trim($_POST['job'] ?? '');
$skills = trim($_POST['skills'] ?? '');
$position = trim($_POST['position'] ?? '');

if ($job === '' || $skills === '' || $position === '') {
    exit('Vyplň prosím všechna pole formuláře.');
}

/*
 * Načtení soukromé konfigurace.
 */
$configFile = __DIR__ . '/config.local.php';

if (!is_file($configFile)) {
    exit('Chybí konfigurační soubor config.local.php.');
}

$config = require $configFile;
$apiKey = trim($config['gemini_api_key'] ?? '');

$aiAnalysis = '';
$aiError = '';
$finishReason = '';

/*
 * Volání Gemini API.
 * Při dočasné chybě se požadavek nejvýše třikrát zopakuje.
 */
if ($apiKey !== '' && function_exists('curl_init')) {

    $prompt = "Jsi kariérní poradce v aplikaci CareerPilot AI.
Pomáháš uchazečům porozumět pracovním nabídkám a připravit se na ně.

Odpovídej česky, srozumitelně, konkrétně a realisticky.
Pracuj pouze s údaji, které máš skutečně k dispozici.
Nevymýšlej zkušenosti, kvalifikaci ani dovednosti uchazeče.
Rozlišuj mezi výslovně uvedenými znalostmi a odhady.
Pokud uchazeč uvede základy technologie, netvrď, že ji vůbec neumí.
Pokud nějaká dovednost není v profilu uvedena, označ ji jako neověřenou,
nikoli automaticky jako dovednost, kterou uchazeč nemá.
Nevydávej shodu dovedností za pravděpodobnost přijetí.

PRACOVNÍ NABÍDKA:
" . $job . "

DOVEDNOSTI UCHAZEČE:
" . $skills . "

OBLAST POZICE:
" . $position . "

Připrav analýzu s těmito pěti částmi:

1. STRUČNÉ SHRNUTÍ POZICE
Vysvětli, co by člověk na této pozici pravděpodobně dělal.

2. CO UCHAZEČ SPLŇUJE
Uveď pouze doložené shody a vysvětli jejich význam.

3. CO CHYBÍ NEBO NENÍ OVĚŘENO
Odděl skutečně doložené nedostatky od dovedností,
o kterých uchazeč pouze neposkytl informace.

4. DOPORUČENÉ DALŠÍ KROKY
Navrhni tři konkrétní a realistické kroky pro zvýšení připravenosti.

5. CELKOVÉ DOPORUČENÍ
Shrň, na co se uchazeč má zaměřit a co si ověřit v nabídce.

Odpověď má mít přibližně 250 až 400 slov.
Dokonči všech pět částí a všechny věty.
Na závěr zkontroluj, že poslední věta není nedokončená.";

    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.4,
            'maxOutputTokens' => 2500
        ]
    ];

    $jsonPayload = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE
    );

    if ($jsonPayload === false) {
        $aiError = 'Nepodařilo se připravit požadavek pro AI.';
    } else {

        $url =
            'https://generativelanguage.googleapis.com/v1beta/models/'
            . 'gemini-3.7-flash:generateContent';

        $response = false;
        $httpCode = 0;
        $curlError = '';
        $errorData = [];
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {

            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 40,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-goog-api-key: ' . $apiKey
                ],
                CURLOPT_POSTFIELDS => $jsonPayload
            ]);

            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );
            $curlError = curl_error($ch);

            curl_close($ch);

            /*
             * Úspěšná odpověď: další pokusy nejsou potřeba.
             */
            if (
                $response !== false
                && $httpCode >= 200
                && $httpCode < 300
            ) {
                break;
            }

            /*
             * Opakujeme pouze dočasné chyby.
             * Chyby klíče, oprávnění nebo kvóty
             * nemá smysl opakovat bez dalšího.
             */
            $temporaryError =
                $response === false
                || in_array(
                    $httpCode,
                    [500, 502, 503, 504],
                    true
                );

            if (!$temporaryError || $attempt === $maxAttempts) {
                break;
            }

            /*
             * Prodleva: 1 sekunda, potom 2 sekundy.
             */
            sleep($attempt);
        }

        if (
            $response !== false
            && $httpCode >= 200
            && $httpCode < 300
        ) {

            $data = json_decode($response, true);
            $candidate = $data['candidates'][0] ?? [];
            $parts = $candidate['content']['parts'] ?? [];

            foreach ($parts as $part) {
                if (
                    isset($part['text'])
                    && is_string($part['text'])
                ) {
                    $aiAnalysis .= $part['text'];
                }
            }

            $finishReason =
                $candidate['finishReason'] ?? 'neznámý';

            error_log(
                'Gemini success diagnostic'
                . ' | finishReason=' . $finishReason
                . ' | textLength=' . strlen($aiAnalysis)
            );

            if (trim($aiAnalysis) === '') {
                $aiError =
                    'Gemini odpovědělo, ale nevrátilo žádný text.';

                error_log(
                    'Gemini returned no text.'
                    . ' | finishReason=' . $finishReason
                );
            }

        } else {

            $apiErrorMessage = 'Odpověď nebyla přijata.';

            if ($response !== false) {
                $errorData = json_decode($response, true);

                $apiErrorMessage =
                    $errorData['error']['message']
                    ?? 'Server vrátil neočekávanou odpověď.';
            }

            /*
             * Diagnostika přímo na stránce.
             * API klíč se nikam nevypisuje.
             */
            $aiError = 'Diagnostika Gemini: HTTP ' . $httpCode;

            if ($curlError !== '') {
                $aiError .= ' | cURL: ' . $curlError;
            }

            $aiError .= ' | API: '
                . substr($apiErrorMessage, 0, 500);

            error_log(
                'Gemini diagnostic'
                . ' | HTTP=' . $httpCode
                . ' | cURL=' . $curlError
                . ' | API=' . substr($apiErrorMessage, 0, 500)
            );
        }
    }

} elseif (!function_exists('curl_init')) {

    $aiError = 'PHP nemá zapnuté rozšíření cURL.';

} else {

    $aiError = 'Chybí API klíč v souboru config.local.php.';
}


/*
 * Slovník dovedností pro pravidlovou analýzu.
 */
$skillDictionary = [
    'HTML' => ['html'],
    'CSS' => ['css', 'css3'],
    'PHP' => ['php'],
    'JavaScript' => ['javascript', 'js'],
    'Python' => ['python'],
    'SQL' => ['sql', 'mysql', 'postgresql'],
    'Excel' => ['excel', 'microsoft excel'],
    'Power BI' => ['power bi'],
    'Tableau' => ['tableau'],
    'Bootstrap' => ['bootstrap'],
    'Git' => ['git', 'github'],
    'Projektové řízení' => [
        'projektové řízení',
        'project management',
        'project manager'
    ],
    'Komunikace' => ['komunikace', 'communication'],
    'Angličtina' => ['angličtina', 'english'],
];

/*
 * Hledání samostatných výrazů.
 */
function containsTerm(string $text, string $term): bool {
    $pattern = '/(?<![\p{L}\p{N}_])'
        . preg_quote($term, '/')
        . '(?![\p{L}\p{N}_])/iu';

    return preg_match($pattern, $text) === 1;
}

$requiredSkills = [];
$userSkills = [];

foreach ($skillDictionary as $skill => $aliases) {

    foreach ($aliases as $alias) {
        if (containsTerm($job, $alias)) {
            $requiredSkills[] = $skill;
            break;
        }
    }

    foreach ($aliases as $alias) {
        if (containsTerm($skills, $alias)) {
            $userSkills[] = $skill;
            break;
        }
    }
}

$matchedSkills = array_values(
    array_intersect($requiredSkills, $userSkills)
);

$missingSkills = array_values(
    array_diff($requiredSkills, $userSkills)
);

$totalRequired = count($requiredSkills);

$matchPercentage = $totalRequired > 0
    ? (int) round(
        count($matchedSkills) / $totalRequired * 100
    )
    : null;

if ($totalRequired === 0) {

    $recommendation =
        'V nabídce se nepodařilo rozpoznat žádnou dovednost '
        . 'z našeho slovníku. Zkus nabídku doplnit '
        . 'o konkrétní technické nebo profesní požadavky.';

} elseif (count($missingSkills) === 0) {

    $recommendation =
        'Z hlediska rozpoznaných dovedností máš dobrou shodu. '
        . 'Ověř ještě požadované zkušenosti a další podmínky.';

} else {

    $recommendation =
        'Zaměř se na chybějící nebo neověřené dovednosti. '
        . 'Vyber si jednu a vytvoř malý praktický projekt, '
        . 'na kterém si ji procvičíš.';
}

?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analýza | CareerPilot AI</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <a href="index.php" class="logo">CareerPilot <span>AI</span></a>
    <span class="status">Výsledky analýzy</span>
</header>

<main class="container">

    <section class="hero">
        <p class="eyebrow">VYHODNOCENÍ NABÍDKY</p>
        <h1>Tvoje kariérní analýza</h1>
        <p class="subtitle">
            Orientační porovnání dovedností s pracovní nabídkou.
        </p>
    </section>

    <section class="form-card">

        <h2>Výsledek analýzy</h2>

        <p>
            <strong>Oblast:</strong>
            <?= escape($position) ?>
        </p>

        <?php if ($matchPercentage !== null): ?>

            <div class="score">
                <div class="score-number">
                    <?= $matchPercentage ?> %
                </div>
                <p>Shoda rozpoznaných dovedností</p>
            </div>

            <div class="progress-track">
                <div
                    class="progress-bar"
                    style="width: <?= $matchPercentage ?>%"
                ></div>
            </div>

            <p class="description">
                Skóre vyjadřuje podíl rozpoznaných požadavků,
                které se podařilo najít i ve tvém profilu.
                Nejde o pravděpodobnost přijetí do zaměstnání.
            </p>

        <?php else: ?>

            <p class="notice">
                Zatím nelze vypočítat skóre shody.
            </p>

        <?php endif; ?>

        <div class="result-section">
            <h3>Dovednosti, které už máš</h3>

            <?php if (count($matchedSkills) > 0): ?>

                <ul class="skill-list">
                    <?php foreach ($matchedSkills as $skill): ?>
                        <li class="skill-matched">
                            <?= escape($skill) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

            <?php else: ?>

                <p>Zatím nebyla nalezena žádná shoda.</p>

            <?php endif; ?>
        </div>

        <div class="result-section">
            <h3>Dovednosti k doplnění</h3>

            <?php if (count($missingSkills) > 0): ?>

                <ul class="skill-list">
                    <?php foreach ($missingSkills as $skill): ?>
                        <li class="skill-missing">
                            <?= escape($skill) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

            <?php elseif ($totalRequired > 0): ?>

                <p>Všechny rozpoznané dovednosti se shodují.</p>

            <?php else: ?>

                <p>Nelze vyhodnotit bez rozpoznaných požadavků.</p>

            <?php endif; ?>
        </div>

        <div class="result-section">
            <h3>AI kariérní poradce</h3>

            <?php if (trim($aiAnalysis) !== ''): ?>

                <div class="ai-response">
                    <?= nl2br(escape($aiAnalysis)) ?>
                </div>

                <?php if ($finishReason === 'MAX_TOKENS'): ?>
                    <p class="notice">
                        Upozornění: AI dosáhla limitu délky odpovědi.
                        Některé části analýzy mohou chybět.
                    </p>
                <?php endif; ?>

            <?php else: ?>

                <p class="notice">
                    <?= escape(
                        $aiError !== ''
                            ? $aiError
                            : 'AI analýza nyní není dostupná.'
                    ) ?>
                </p>

                <p>
                    Pravidlová analýza výše zůstává dostupná
                    i v případě výpadku AI.
                </p>

            <?php endif; ?>
        </div>

        <div class="recommendation">
            <h3>Doporučený další krok</h3>
            <p><?= escape($recommendation) ?></p>
        </div>

        <a href="index.php" class="back-link">
            ← Analyzovat další nabídku
        </a>

    </section>

</main>

<footer>
    <p>CareerPilot AI · Studentský projekt</p>
</footer>

</body>
</html>
