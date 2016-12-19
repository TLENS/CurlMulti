# CurlMulti
Multi threaded curl requests

## Usage examples
```php
include 'CurlMulti.php';
$microtime = microtime(true);
function getCurlHandle($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:10.0) Gecko/20150101 Firefox/47.0 (Chrome)');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    return $ch;
}
$allTime = 0.0;

for ($i = 0; $i <= 100; $i++) {
    $ch = getCurlHandle("https://www.youtube.com/results?search_query=request+$i");
    curl_setopt($ch, CURLOPT_NOBODY, true);
    $chm = new CurlMulti($ch);
    $chm->callbackOk = function ($content, $info) use (&$allTime) {
        $allTime += $info['total_time'];
    };
}
CurlMulti::exec();
echo "php execute time: " . round(microtime(true) - $microtime, 2) . PHP_EOL;
echo "all curl times:" . round($allTime, 2) . "s.\n";
exit;
```
- Result
php execute time: 11.15
all curl times:921.3s.
