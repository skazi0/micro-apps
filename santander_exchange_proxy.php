<?php
$c = 'EUR / PLN';
$d = date('Y-m-d');
$url = "https://www.santander.pl/klient-indywidualny/karty-platnosci-i-kantor/kantor-santander?action=component_request.action&component.action=getRates&component.id=2451485";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:103.0) Gecko/20100101 Firefox/103.0');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$content = curl_exec($ch);
curl_close($ch);

$json = json_decode($content, true);
// all rates
$rates = $json['rates'];
$json = array();
foreach ($rates as $r) {
    if ($r['label'] == $c) {
        $json = $r;
        break;
    }
}
// map to old structure
$json['a'] = floatval(str_replace(',', '.', $json['wantToBuyRate']));
$json['b'] = floatval(str_replace(',', '.', $json['wantToSellRate']));

if ($json['a'] == 0 || $json['b'] == 0) {
    http_response_code(503);
    die("Invalid/zero rates!");
}

header('Content-Type: application/json');
print json_encode($json, JSON_PRETTY_PRINT);
