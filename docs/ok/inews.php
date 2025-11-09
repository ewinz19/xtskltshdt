<?php
// playlist.php - Ambil m3u8 terbaru dari embed RCTI+ dan buat M3U dinamis
header('Content-Type: application/vnd.apple.mpegurl; charset=utf-8');
header('Content-Disposition: inline; filename="inews.m3u"');
header('Cache-Control: no-cache, must-revalidate, max-age=0');

// ----------------------
// STEP 1: fetch embed page dengan cURL
$embed_url = 'https://embed.rctiplus.com/live/inews/inewsid';
$ch = curl_init($embed_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0 Safari/537.36',
    'Referer: https://embed.rctiplus.com/'
]);
$html = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if (!$html) {
    http_response_code(502);
    echo "## ERROR: cannot fetch embed page ($err)";
    exit;
}

// ----------------------
// STEP 2: cari URL .m3u8
// Embed biasanya punya URL seperti https://icdn.rctiplus.id/inews-sdi.m3u8?hdnts=...
preg_match('/https:\/\/icdn\.rctiplus\.id\/inews-sdi\.m3u8\?hdnts=[^"\']+/', $html, $matches);

if (empty($matches)) {
    http_response_code(502);
    echo "## ERROR: cannot find m3u8 URL";
    exit;
}

$stream_url = $matches[0];

// ----------------------
// STEP 3: buat M3U dinamis
$logo = 'https://github.com/ewinz19/xtskltshdt/blob/main/icons/inews.png?raw=true';
$group = 'MY TV';
$title = 'inews';

echo "#EXTM3U\n";
echo "#EXTINF:-1 tvg-id=\"inews.id\" tvg-logo=\"{$logo}\" group-title=\"{$group}\",{$title}\n";
echo "#KODIPROP:inputstream=inputstream.adaptive\n";
echo "#KODIPROP:inputstreamaddon=inputstream.adaptive\n";
echo "#EXTVLCOPT:http-user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0 Safari/537.36\n";
echo "#EXTVLCOPT:http-referrer=https://embed.rctiplus.com/\n";
echo "####\n";
echo $stream_url . "\n";
