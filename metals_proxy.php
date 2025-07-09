<?php
$types = ['silver', 'gold'];
$agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:103.0) Gecko/20100101 Firefox/103.0';
function get_rate($type) {
    global $agent;
#    $url = "https://goldenmark.com/wp-json/gmsc/v1/rates/${type}_pln/0/day";
    $url = "https://goldenmark.com/blog/wp-json/gmsc/v1/rates/${type}_pln/1/month";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $content = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($content);
    if (json_last_error() != JSON_ERROR_NONE) {
        die('JSON decoding error: '.json_last_error_msg().', input: \''.$content.'\'');
    }
    $last= end($json);
    if (!is_array($last)) {
        die('Unexpected response: \''.$content.'\'');
    }
    return Array( 'rate' => $last[1], 'time' => intval($last[0]) );
}

function get_rate1($type) {
    global $agent;
    $ts = round(microtime(true)*1000);
    $ts2 = $ts+1;
    $cb = "jQuery3610728713133403863_${ts}";
    $url = "https://www.coininvest.com/charts/get-data/?metal_code=${type}&currency_id=4&weight_code=oz&period=1day&callback=${cb}&_=${ts2}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $content = curl_exec($ch);
    curl_close($ch);
    // unwrap cb
    $content = preg_replace("/".$cb."\({data:|, source:.*/", '', $content);
    $json = json_decode($content);
    $last= end($json);
    return Array( 'rate' => $last[1], 'time' => intval($last[0]/1000) );
}

function get_rate2() {
    global $agent;
    // set currency
    $ch = curl_init("https://stonexbullion.com/en/?change=1&curRate=zloty_rate");
    curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/metalcookie.txt');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $content = curl_exec($ch);
    // fetch rates
    $ch = curl_init("https://stonexbullion.com/ajax/spot-rates/");
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/metalcookie.txt');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $content = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($content);
    $ret = Array();
    foreach ($json->data as $type => $data) {
        $ret[$type] = Array( 'rate' => $data->price );
    }
    return $ret;
}

function get_rate3() {
    global $agent;
    // get nbp pln/eur rate
    $ch = curl_init("http://api.nbp.pl/api/exchangerates/rates/A/EUR?format=json");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $content = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($content);
    $currency = $json->rates[0]->mid;
    // fetch eur metal rates
    $ch = curl_init("https://stonexbullion.com/api/client/spot-rates/");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $content = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($content);
    if (json_last_error() != JSON_ERROR_NONE) {
        die('JSON decoding error: '.json_last_error_msg().', input: \''.$content.'\'');
    }
    if ($json->status == 'Error') {
        die('API returned error: '.$json->message);
    }
    $ret = Array();
    foreach ($json->data as $type => $data) {
        $ret[$type] = Array( 'rate' => $data->price * $currency );
    }
    return $ret;
}

function get_rate4($type) {
    global $agent;
    // get nbp pln/eur rate
    $ch = curl_init("http://api.nbp.pl/api/exchangerates/rates/A/EUR?format=json");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $content = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($content);
    $currency = $json->rates[0]->mid;
    // fetch eur metal rates
    $ch = curl_init("https://stonexbullion.com/api/client/charts/data?period=all&metal_code=".$type."&weight_code=oz");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $content = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($content);
    if (json_last_error() != JSON_ERROR_NONE) {
        die('JSON decoding error: '.json_last_error_msg().', input: \''.$content.'\'');
    }
    if (!$json->success) {
        die('API returned error: '.$jcontent);
    }
    $price = 0;
    $maxts = 0;
    foreach ($json->data->ranges as $point) {
        if ($maxts < $point[0]) {
            $maxts = $point[0];
            $price = $point[1];
        }
    }
    return Array('rate' => $price * $currency);
}
$rates = Array();
foreach ($types as $type) {
    $rates[$type] = get_rate($type);
}
//$rates = get_rate3();
header('Content-Type: application/json');
print json_encode($rates, JSON_PRETTY_PRINT);
