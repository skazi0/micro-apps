<?php
$apikey = trim(file_get_contents('/etc/nginx/alphavantageapikey'));
$symbol = $_GET['symbol'];
if (!$symbol) {
    http_response_code(503);
    die("Empty symbol.");
}
$url = 'https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol='.$symbol.'&apikey='.$apikey;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$content = curl_exec($ch);
curl_close($ch);
if (!$content) {
    http_response_code(503);
    die("Empty content. Wrong API key?");
}
$json = json_decode($content);
$ret = Array('price' => floatval($json->{'Global Quote'}->{'05. price'}), 'date' => $json->{'Global Quote'}->{'07. latest trading day'});
header('Content-Type: application/json');
print json_encode($ret, JSON_PRETTY_PRINT);
