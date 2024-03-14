<?php
$table = $_GET['table'];
if ($table != 'A' && $table != 'C') {
    http_response_code(503);
    die("Bad table. Only 'A' and 'C' supported.");
}
# TODO: use http://api.nbp.pl/
$url = 'https://static.nbp.pl/dane/kursy/xml/Last'.$table.'.xml';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:107.0) Gecko/20100101 Firefox/107.0');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$content = curl_exec($ch);
curl_close($ch);

if (strpos($content, 'tabela_kursow') === false) {
    http_response_code(503);
    die("Browser identification failed. Update agent string");
}

header('Content-Type: text/xml');
print $content;
