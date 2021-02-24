<?php
$c = 'EUR/PLN';
$d = date('Y-m-d');
$url = "https://www.centrum24.pl/efx/polling?c=$c&d=$d";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$content = curl_exec($ch);
curl_close($ch);

$json = json_decode($content, true);
// all ranges
$ranges = $json['fxData'][$c]['r'];
// select range with lowest floor
$minrange = $ranges[0];
foreach ($ranges as $range) {
    if ((float)$minrange['f'] > (float)$range['f']) {
        $minrange = $range;
    }
}
$json = $minrange['rt'][0];

header('Content-Type: application/json');
print json_encode($json, JSON_PRETTY_PRINT);
