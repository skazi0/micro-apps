<?php
$url = 'https://klient.internetowykantor.pl/api/public/marketBrief';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$content = curl_exec($ch);
curl_close($ch);

$json = json_decode($content);

if (empty($json)) {
    http_response_code(503);
    die("Empty exchange rates table found.");
}

$data = Array();
foreach ($json as $pair) {
    $curr = explode('_', $pair->pair);
    if ($curr[1] !== 'PLN')
        continue;
    $data[$curr[0]] = Array('buy' => $pair->directExchangeOffers->buyNow, 'sell' => $pair->directExchangeOffers->sellNow);
}


header('Content-Type: application/json');
print json_encode($data, JSON_PRETTY_PRINT);
