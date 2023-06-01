<?php
$types = ['silver', 'gold'];
function get_rate($type) {
#    $url = "https://goldenmark.com/wp-json/gmsc/v1/rates/${type}_pln/0/day";
    $url = "https://goldenmark.com/wp-json/gmsc/v1/rates/${type}_pln/1/month";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:103.0) Gecko/20100101 Firefox/103.0');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $content = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($content);
    $last= end($json);
    return Array( 'rate' => $last[1], 'time' => intval($last[0]) );
}

function get_rate1($type) {
    $ts = round(microtime(true)*1000);
    $ts2 = $ts+1;
    $cb = "jQuery3610728713133403863_${ts}";
    $url = "https://www.coininvest.com/charts/get-data/?metal_code=${type}&currency_id=4&weight_code=oz&period=1day&callback=${cb}&_=${ts2}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:103.0) Gecko/20100101 Firefox/103.0');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $content = curl_exec($ch);
    curl_close($ch);
    // unwrap cb
    $content = preg_replace("/".$cb."\({data:|, source:.*/", '', $content);
    $json = json_decode($content);
    $last= end($json);
    return Array( 'rate' => $last[1], 'time' => intval($last[0]/1000) );
}

$rates = Array();
foreach ($types as $type) {
    $rates[$type] = get_rate1($type);
}

header('Content-Type: application/json');
print json_encode($rates, JSON_PRETTY_PRINT);
