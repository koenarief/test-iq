<?php

declare(strict_types=1);

/**
 * Generator SVG FA final.
 *
 * Konsep:
 * - seluruh bentuk menggunakan garis lurus;
 * - potongan boleh diputar;
 * - tidak menggunakan mirror;
 * - warna terang + outline gelap;
 * - setiap soal memiliki 5 target A-E;
 * - prompt terdiri dari 2-4 potongan.
 */

$base = dirname(__DIR__)
    . '/database/data/ist-final-staging/media/fa';

$exampleDir = $base . '/examples';
$questionDir = $base . '/questions';
$optionDir = $base . '/options';

foreach ([$exampleDir, $questionDir, $optionDir] as $dir) {
    if (! is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

/*
|--------------------------------------------------------------------------
| STYLE
|--------------------------------------------------------------------------
*/

$fill = '#f8fafc';
$stroke = '#0f172a';
$strokeWidth = 3;

/*
|--------------------------------------------------------------------------
| SVG HELPERS
|--------------------------------------------------------------------------
*/

function svgStart(
    int $width,
    int $height,
    int $viewWidth,
    int $viewHeight
): string {
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg"
     width="{$width}"
     height="{$height}"
     viewBox="0 0 {$viewWidth} {$viewHeight}"
     role="img"
     aria-hidden="true">

SVG;
}

function svgEnd(): string
{
    return "</svg>\n";
}

function polygon(
    string $points,
    string $fill,
    string $stroke,
    int $strokeWidth = 3,
    ?string $transform = null
): string {
    $transformAttribute = $transform
        ? ' transform="' . $transform . '"'
        : '';

    return <<<SVG
    <polygon
        points="{$points}"
        fill="{$fill}"
        stroke="{$stroke}"
        stroke-width="{$strokeWidth}"
        stroke-linejoin="round"
        {$transformAttribute}
    />

SVG;
}

function writeSvg(string $path, string $content): void
{
    file_put_contents($path, $content);

    echo "GENERATED: {$path}\n";
}

/*
|--------------------------------------------------------------------------
| OPTION SILHOUETTES
|--------------------------------------------------------------------------
|
| Kita memakai 5 keluarga bentuk yang mudah dibedakan:
|
| A = Persegi
| B = Persegi panjang
| C = Segitiga
| D = Trapesium
| E = Jajargenjang
|
*/

function optionSvg(string $key): string
{
    global $fill, $stroke, $strokeWidth;

    $svg = svgStart(240, 180, 240, 180);

    $svg .= match ($key) {
        'A' => polygon(
            '80,45 160,45 160,125 80,125',
            $fill,
            $stroke,
            $strokeWidth
        ),

        'B' => polygon(
            '50,60 190,60 190,120 50,120',
            $fill,
            $stroke,
            $strokeWidth
        ),

        'C' => polygon(
            '120,35 190,130 50,130',
            $fill,
            $stroke,
            $strokeWidth
        ),

        'D' => polygon(
            '80,45 160,45 195,130 45,130',
            $fill,
            $stroke,
            $strokeWidth
        ),

        'E' => polygon(
            '90,45 190,45 150,130 50,130',
            $fill,
            $stroke,
            $strokeWidth
        ),

        default => throw new RuntimeException(
            "Unknown option key: {$key}"
        ),
    };

    return $svg . svgEnd();
}

/*
|--------------------------------------------------------------------------
| PROMPT GENERATORS
|--------------------------------------------------------------------------
|
| Setiap prompt dibuat dari potongan polygon terpisah.
| Posisi potongan sengaja dipisahkan dan sebagian diputar.
|
*/

function promptExample(): string
{
    global $fill, $stroke, $strokeWidth;

    $svg = svgStart(360, 200, 360, 200);

    /*
     * Dua potongan yang mengarah ke persegi panjang (B).
     */

    $svg .= polygon(
        '45,65 120,65 120,125 45,125',
        $fill,
        $stroke,
        $strokeWidth
    );

    $svg .= polygon(
        '215,65 280,65 300,95 280,125 215,125',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(18 255 95)'
    );

    return $svg . svgEnd();
}

function prompt001(): string
{
    global $fill, $stroke, $strokeWidth;

    $svg = svgStart(360, 200, 360, 200);

    // EASY — target C
    $svg .= polygon(
        '45,135 105,135 105,70',
        $fill,
        $stroke,
        $strokeWidth
    );

    $svg .= polygon(
        '220,65 295,135 220,135',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(-18 255 100)'
    );

    return $svg . svgEnd();
}

function prompt002(): string
{
    global $fill, $stroke, $strokeWidth;

    $svg = svgStart(360, 200, 360, 200);

    // EASY — target A
    $svg .= polygon(
        '45,60 115,60 115,130 80,130',
        $fill,
        $stroke,
        $strokeWidth
    );

    $svg .= polygon(
        '225,65 260,65 295,100 260,135 225,135',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(25 260 100)'
    );

    return $svg . svgEnd();
}

function prompt003(): string
{
    global $fill, $stroke, $strokeWidth;

    $svg = svgStart(360, 200, 360, 200);

    // EASY — target E
    $svg .= polygon(
        '45,65 115,65 95,135 45,135',
        $fill,
        $stroke,
        $strokeWidth
    );

    $svg .= polygon(
        '230,60 300,60 280,130 210,130',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(-20 255 95)'
    );

    return $svg . svgEnd();
}

function prompt004(): string
{
    global $fill, $stroke, $strokeWidth;

    $svg = svgStart(420, 220, 420, 220);

    // MEDIUM — target D
    $svg .= polygon(
        '40,65 105,65 90,135 40,135',
        $fill,
        $stroke,
        $strokeWidth
    );

    $svg .= polygon(
        '165,55 220,55 235,115 180,115',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(22 200 85)'
    );

    $svg .= polygon(
        '300,75 350,75 375,135 320,135',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(-16 335 105)'
    );

    return $svg . svgEnd();
}

function prompt005(): string
{
    global $fill, $stroke, $strokeWidth;

    $svg = svgStart(420, 220, 420, 220);

    // MEDIUM — target B
    $svg .= polygon(
        '35,70 100,70 100,130 35,130',
        $fill,
        $stroke,
        $strokeWidth
    );

    $svg .= polygon(
        '165,65 225,65 225,125 165,125',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(90 195 95)'
    );

    $svg .= polygon(
        '305,65 365,65 365,125 305,125',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(-20 335 95)'
    );

    return $svg . svgEnd();
}

function prompt006(): string
{
    global $fill, $stroke, $strokeWidth;

    $svg = svgStart(420, 220, 420, 220);

    // MEDIUM — target C
    $svg .= polygon(
        '45,135 100,135 100,75',
        $fill,
        $stroke,
        $strokeWidth
    );

    $svg .= polygon(
        '165,130 225,130 195,75',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(25 195 105)'
    );

    $svg .= polygon(
        '295,135 365,135 330,70',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(-20 330 105)'
    );

    return $svg . svgEnd();
}

function prompt007(): string
{
    global $fill, $stroke, $strokeWidth;

    $svg = svgStart(420, 220, 420, 220);

    // MEDIUM — target A
    $svg .= polygon(
        '40,65 100,65 100,125 40,125',
        $fill,
        $stroke,
        $strokeWidth
    );

    $svg .= polygon(
        '165,55 225,55 225,115 165,115',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(45 195 85)'
    );

    $svg .= polygon(
        '305,65 365,65 365,125 305,125',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(-25 335 95)'
    );

    return $svg . svgEnd();
}

function prompt008(): string
{
    global $fill, $stroke, $strokeWidth;

    $svg = svgStart(480, 240, 480, 240);

    // HARD — target E
    $svg .= polygon(
        '35,70 95,70 75,130 35,130',
        $fill,
        $stroke,
        $strokeWidth
    );

    $svg .= polygon(
        '145,65 205,65 185,125 125,125',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(30 165 95)'
    );

    $svg .= polygon(
        '265,65 325,65 305,125 245,125',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(-35 285 95)'
    );

    $svg .= polygon(
        '385,70 445,70 425,130 365,130',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(65 405 100)'
    );

    return $svg . svgEnd();
}

function prompt009(): string
{
    global $fill, $stroke, $strokeWidth;

    $svg = svgStart(480, 240, 480, 240);

    // HARD — target D
    $svg .= polygon(
        '30,70 90,70 75,130 30,130',
        $fill,
        $stroke,
        $strokeWidth
    );

    $svg .= polygon(
        '145,60 205,60 220,120 160,120',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(28 180 90)'
    );

    $svg .= polygon(
        '265,70 325,70 340,130 280,130',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(-30 300 100)'
    );

    $svg .= polygon(
        '385,65 440,65 455,125 400,125',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(50 420 95)'
    );

    return $svg . svgEnd();
}

function prompt010(): string
{
    global $fill, $stroke, $strokeWidth;

    $svg = svgStart(480, 240, 480, 240);

    // HARD — target B
    $svg .= polygon(
        '30,70 90,70 90,130 30,130',
        $fill,
        $stroke,
        $strokeWidth
    );

    $svg .= polygon(
        '145,70 205,70 205,130 145,130',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(30 175 100)'
    );

    $svg .= polygon(
        '265,70 325,70 325,130 265,130',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(-40 295 100)'
    );

    $svg .= polygon(
        '385,70 445,70 445,130 385,130',
        $fill,
        $stroke,
        $strokeWidth,
        'rotate(65 415 100)'
    );

    return $svg . svgEnd();
}

/*
|--------------------------------------------------------------------------
| GENERATE EXAMPLE
|--------------------------------------------------------------------------
*/

writeSvg(
    $exampleDir . '/fa-example-001-prompt.svg',
    promptExample()
);

foreach (['A', 'B', 'C', 'D', 'E'] as $key) {
    writeSvg(
        $exampleDir
            . '/fa-example-001-option-'
            . strtolower($key)
            . '.svg',
        optionSvg($key)
    );
}

/*
|--------------------------------------------------------------------------
| GENERATE SCORED QUESTIONS
|--------------------------------------------------------------------------
*/

$prompts = [
    1 => prompt001(),
    2 => prompt002(),
    3 => prompt003(),
    4 => prompt004(),
    5 => prompt005(),
    6 => prompt006(),
    7 => prompt007(),
    8 => prompt008(),
    9 => prompt009(),
    10 => prompt010(),
];

foreach ($prompts as $number => $svg) {
    $numberString = str_pad((string) $number, 3, '0', STR_PAD_LEFT);

    writeSvg(
        "{$questionDir}/fa-q{$numberString}-prompt.svg",
        $svg
    );

    foreach (['A', 'B', 'C', 'D', 'E'] as $key) {
        writeSvg(
            "{$optionDir}/fa-q{$numberString}-option-"
                . strtolower($key)
                . '.svg',
            optionSvg($key)
        );
    }
}

/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

echo PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "FA SVG GENERATION COMPLETE" . PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "Example : 1 prompt + 5 options" . PHP_EOL;
echo "Scored  : 10 prompts + 50 options" . PHP_EOL;
echo "Total   : 66 SVG files" . PHP_EOL;
echo PHP_EOL;

echo "Answer key planned:" . PHP_EOL;
echo "EXAMPLE = B" . PHP_EOL;
echo "001 = C" . PHP_EOL;
echo "002 = A" . PHP_EOL;
echo "003 = E" . PHP_EOL;
echo "004 = D" . PHP_EOL;
echo "005 = B" . PHP_EOL;
echo "006 = C" . PHP_EOL;
echo "007 = A" . PHP_EOL;
echo "008 = E" . PHP_EOL;
echo "009 = D" . PHP_EOL;
echo "010 = B" . PHP_EOL;