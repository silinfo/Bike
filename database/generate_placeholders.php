<?php
// Genera las ilustraciones SVG de ejemplo en assets/img/products.
// Uso: php database/generate_placeholders.php
// Sustitúyelas por fotos reales desde el panel de administración.

$out = __DIR__ . '/../assets/img/products';
if (!is_dir($out)) mkdir($out, 0775, true);

function bike_svg(string $color, string $style): string
{
    // Geometría base (vista lateral)
    $rear = [190, 330]; $front = [610, 330];
    $bb = [370, 330]; $seat = [330, 170]; $head = [540, 175];
    $r = 105; $tire = 12;
    $extra = '';
    $bar = '<path d="M540 175 L555 140 L600 135" stroke="#111" stroke-width="10" fill="none" stroke-linecap="round"/>';
    $fork = sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="14" stroke-linecap="round"/>', $head[0], $head[1], $front[0], $front[1], $color);

    switch ($style) {
        case 'road':
            $bar = '<path d="M540 175 L560 145 L605 145 Q640 150 625 185 Q615 200 600 195" stroke="#111" stroke-width="9" fill="none" stroke-linecap="round"/>';
            $tire = 8;
            break;
        case 'gravel':
            $bar = '<path d="M540 175 L560 145 L605 145 Q645 155 630 190" stroke="#111" stroke-width="9" fill="none" stroke-linecap="round"/>';
            $tire = 16;
            $extra .= '<rect x="300" y="190" width="120" height="44" rx="14" fill="#1a1a1a" opacity=".85" transform="rotate(-8 360 212)"/>';
            break;
        case 'mtb':
            $tire = 22;
            $bar = '<path d="M540 175 L552 135 M515 130 L600 130" stroke="#111" stroke-width="10" fill="none" stroke-linecap="round"/>';
            $fork = sprintf('<line x1="540" y1="175" x2="575" y2="255" stroke="#222" stroke-width="22" stroke-linecap="round"/><line x1="575" y1="255" x2="%d" y2="%d" stroke="#bbb" stroke-width="12" stroke-linecap="round"/>', $front[0], $front[1]);
            $extra .= '<rect x="385" y="210" width="60" height="20" rx="8" fill="#222" transform="rotate(-35 415 220)"/>';
            break;
        case 'urban':
            $tire = 14;
            $bar = '<path d="M540 175 L545 130 Q575 110 610 130" stroke="#111" stroke-width="9" fill="none" stroke-linecap="round"/>';
            $extra .= '<path d="M90 300 A110 110 0 0 1 250 235" stroke="#222" stroke-width="8" fill="none"/><path d="M510 235 A110 110 0 0 1 700 290" stroke="#222" stroke-width="8" fill="none"/><rect x="170" y="200" width="150" height="10" fill="#222"/>';
            $seat = [310, 175];
            break;
        case 'fixie':
            $tire = 9;
            $bar = '<path d="M540 175 L555 150 L610 150" stroke="#111" stroke-width="9" fill="none" stroke-linecap="round"/>';
            break;
        case 'ebike':
            $tire = 18;
            $extra .= '<rect x="400" y="205" width="130" height="36" rx="12" fill="#111" transform="rotate(-37 465 223)"/><circle cx="370" cy="330" r="30" fill="#111"/>';
            break;
    }

    $frame = sprintf(
        '<path d="M%1$d %2$d L%3$d %4$d L%5$d %6$d L%7$d %8$d Z" stroke="%9$s" stroke-width="16" fill="none" stroke-linejoin="round"/>' .
        '<path d="M%3$d %4$d L%10$d %11$d L%5$d %6$d" stroke="%9$s" stroke-width="12" fill="none" stroke-linejoin="round"/>',
        $bb[0], $bb[1], $seat[0], $seat[1], $head[0], $head[1] + 55, $bb[0], $bb[1], $color, $rear[0], $rear[1]
    );
    $topTube = sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="16" stroke-linecap="round"/>', $seat[0], $seat[1], $head[0], $head[1], $color);
    $headTube = sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="18" stroke-linecap="round"/>', $head[0], $head[1], $head[0] + 12, $head[1] + 55, $color);

    $wheel = function ($c) use ($r, $tire) {
        [$x, $y] = $c;
        $spokes = '';
        for ($i = 0; $i < 12; $i++) {
            $a = deg2rad($i * 30);
            $spokes .= sprintf('<line x1="%d" y1="%d" x2="%.1f" y2="%.1f" stroke="#999" stroke-width="1.5"/>', $x, $y, $x + cos($a) * ($r - 8), $y + sin($a) * ($r - 8));
        }
        return sprintf('<circle cx="%d" cy="%d" r="%d" stroke="#111" stroke-width="%d" fill="none"/><circle cx="%d" cy="%d" r="%d" stroke="#444" stroke-width="3" fill="none"/>%s<circle cx="%d" cy="%d" r="8" fill="#111"/>',
            $x, $y, $r, $tire, $x, $y, $r - $tire / 2 - 3, $spokes, $x, $y);
    };

    $saddle = sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#111" stroke-width="8"/><path d="M%d %d L%d %d Q%d %d %d %d Z" fill="#111"/>',
        $seat[0], $seat[1], $seat[0] - 10, $seat[1] - 35, $seat[0] - 50, $seat[1] - 40, $seat[0] + 30, $seat[1] - 40, $seat[0] + 30, $seat[1] - 28, $seat[0] - 50, $seat[1] - 32);
    $crank = sprintf('<circle cx="%d" cy="%d" r="22" fill="none" stroke="#111" stroke-width="6"/><line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#111" stroke-width="8" stroke-linecap="round"/>',
        $bb[0], $bb[1], $bb[0], $bb[1], $bb[0] + 30, $bb[1] + 40);

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 480"><rect width="800" height="480" fill="#f1f0ec"/>'
        . '<ellipse cx="400" cy="445" rx="320" ry="10" fill="#000" opacity=".07"/>'
        . $wheel($rear) . $wheel($front) . $extra . $frame . $topTube . $headTube . $fork . $bar . $saddle . $crank
        . '</svg>';
}

function accessory_svg(string $color, string $kind): string
{
    $shape = match ($kind) {
        'helmet' => '<path d="M230 300 Q230 140 400 130 Q570 140 570 300 Z" fill="' . $color . '"/><path d="M270 230 Q400 170 530 230" stroke="#111" stroke-width="14" fill="none" opacity=".5"/><path d="M310 190 L330 250 M400 160 L400 240 M490 190 L470 250" stroke="#111" stroke-width="14" stroke-linecap="round" opacity=".5"/><rect x="220" y="295" width="360" height="18" rx="9" fill="#111"/>',
        'jersey' => '<path d="M300 110 L360 100 Q400 130 440 100 L500 110 L600 190 L550 250 L510 220 L510 390 L290 390 L290 220 L250 250 L200 190 Z" fill="' . $color . '"/><rect x="290" y="250" width="220" height="30" fill="#111" opacity=".8"/><line x1="400" y1="110" x2="400" y2="210" stroke="#111" stroke-width="4"/>',
        'bib' => '<path d="M320 90 L350 90 L360 180 L440 180 L450 90 L480 90 L490 200 L520 390 L420 390 L400 280 L380 390 L280 390 L310 200 Z" fill="' . $color . '"/><rect x="280" y="360" width="100" height="30" fill="#111"/><rect x="420" y="360" width="100" height="30" fill="#111"/>',
        'light' => '<rect x="260" y="190" width="240" height="100" rx="30" fill="#1a1a1a"/><circle cx="470" cy="240" r="38" fill="' . $color . '"/><path d="M510 200 L640 150 L640 330 L510 280 Z" fill="' . $color . '" opacity=".25"/><rect x="200" y="225" width="70" height="30" rx="8" fill="#555"/>',
        'rear' => '<rect x="330" y="130" width="140" height="230" rx="40" fill="#1a1a1a"/><rect x="355" y="160" width="90" height="170" rx="28" fill="' . $color . '"/><circle cx="400" cy="245" r="22" fill="#fff" opacity=".6"/>',
        'saddlebag' => '<path d="M220 200 L520 180 Q600 190 600 240 Q600 290 520 300 L220 280 Z" fill="' . $color . '"/><path d="M300 190 L310 290 M420 184 L430 296" stroke="#111" stroke-width="10" opacity=".6"/><rect x="570" y="170" width="20" height="140" rx="6" fill="#111"/>',
        'pannier' => '<rect x="260" y="150" width="280" height="240" rx="26" fill="' . $color . '"/><path d="M260 210 L540 210" stroke="#111" stroke-width="8" opacity=".5"/><path d="M340 150 Q400 80 460 150" stroke="#111" stroke-width="14" fill="none"/><rect x="370" y="230" width="60" height="20" rx="4" fill="#111" opacity=".6"/>',
        'wheels' => '<circle cx="290" cy="240" r="140" stroke="#111" stroke-width="30" fill="none"/><circle cx="290" cy="240" r="115" stroke="' . $color . '" stroke-width="18" fill="none"/><circle cx="510" cy="240" r="140" stroke="#111" stroke-width="30" fill="none"/><circle cx="510" cy="240" r="115" stroke="' . $color . '" stroke-width="18" fill="none"/><circle cx="290" cy="240" r="14" fill="#111"/><circle cx="510" cy="240" r="14" fill="#111"/>',
        'saddle' => '<path d="M200 220 Q220 180 330 190 L560 200 Q610 205 610 235 Q610 260 560 262 L360 272 Q260 290 210 262 Q190 245 200 220 Z" fill="#1a1a1a"/><path d="M300 215 L560 225" stroke="' . $color . '" stroke-width="10" stroke-linecap="round"/><path d="M340 272 L380 340 L470 340 L500 265" stroke="#888" stroke-width="8" fill="none"/>',
        default => '<circle cx="400" cy="240" r="120" fill="' . $color . '"/>',
    };
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 480"><rect width="800" height="480" fill="#f1f0ec"/>'
        . '<ellipse cx="400" cy="430" rx="220" ry="10" fill="#000" opacity=".07"/>' . $shape . '</svg>';
}

$bikes = [
    'aero-r1-carbon'   => ['#d7263d', 'road'],
    'endurance-e3'     => ['#1b3a5c', 'road'],
    'nomad-gx'         => ['#6b7f3a', 'gravel'],
    'trail-gravel-al'  => ['#c27b2c', 'gravel'],
    'ridge-140-trail'  => ['#e4572e', 'mtb'],
    'summit-ht'        => ['#2a9d8f', 'mtb'],
    'city-classic'     => ['#264653', 'urban'],
    'fixie-street'     => ['#111111', 'fixie'],
    'e-urban-volt'     => ['#5e548e', 'ebike'],
    'e-trail-power'    => ['#3d5a80', 'ebike'],
];
$acc = [
    'casco-aero-pro'      => ['#d7263d', 'helmet'],
    'casco-trail-mtb'     => ['#6b7f3a', 'helmet'],
    'maillot-team'        => ['#d7263d', 'jersey'],
    'culotte-bib-pro'     => ['#222222', 'bib'],
    'luz-delantera-1200'  => ['#f4d35e', 'light'],
    'luz-trasera-radar'   => ['#e63946', 'rear'],
    'bolsa-sillin-12l'    => ['#3a3a3a', 'saddlebag'],
    'alforja-urbana'      => ['#8d6e4f', 'pannier'],
    'ruedas-carbon-45'    => ['#d7263d', 'wheels'],
    'sillin-comfort-pro'  => ['#d7263d', 'saddle'],
];

foreach ($bikes as $slug => [$c, $s]) file_put_contents("$out/$slug.svg", bike_svg($c, $s));
foreach ($acc as $slug => [$c, $k]) file_put_contents("$out/$slug.svg", accessory_svg($c, $k));
file_put_contents("$out/placeholder.svg", bike_svg('#bbbbbb', 'road'));
echo "Generadas " . (count($bikes) + count($acc) + 1) . " imágenes en $out\n";
