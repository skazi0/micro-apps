<?php
$url = 'https://www.pekao.com.pl/kursy-walut/lista-walut.html';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$content = curl_exec($ch);
curl_close($ch);
$content = preg_replace('/&nbsp;/', ' ', $content);
$content = preg_replace('/\s+/m', ' ', $content);

if (strpos($content, 'Strona błędu przeglądarki') !== false) {
    http_response_code(503);
    die("Browser identification failed. Update agent string");
}

if (preg_match('/currenciesJson\s*=\s*(.*?);\s*<\/script>/', $content, $matches) === false) {
    http_response_code(503);
    die("No exchange rates found.");
}

$json = json_decode($matches[1]);

if (empty($json->exchangeRatesDTO->rates)) {
    http_response_code(503);
    die("Empty exchange rates table found.");
}

header('Content-Type: application/json');
print json_encode($json, JSON_PRETTY_PRINT);
