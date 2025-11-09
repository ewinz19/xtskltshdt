<?php
// playlist.php
// URL contohnya: https://yourserver.com/playlist.php
// Pastikan menggunakan HTTPS

// Konfigurasi: endpoint yang aman (tes.php) dan secret
$tes_endpoint = 'https://winz.linkpc.net/ok/inews.php';
$secret = 'SECRETPASS';

// Ambil data dari tes.php
$opts = [
    "http" => [
        "method" => "GET",
        // tambahkan user agent dan timeouts
        "header" => "User-Agent: MyPlaylistServer/1.0\r\n",
        "timeout" => 5
    ],
    "ssl" => [
        "verify_peer" => true,
        "verify_peer_name" => true
    ]
];
$context = stream_context_create($opts);

// panggil endpoint dengan secret: contoh menambahkan query ?key=...
$fetch_url = $tes_endpoint . '?key=' . urlencode($secret);
$json = @file_get_contents($fetch_url, false, $context);

if ($json === false) {
    header('HTTP/1.1 502 Bad Gateway');
    echo "## ERROR: cannot fetch stream data";
    exit;
}

$data = json_decode($json, true);
if (!is_array($data) || empty($data['stream_url'])) {
    header('HTTP/1.1 502 Bad Gateway');
    echo "## ERROR: invalid stream data";
    exit;
}

// Ambil nilai
$stream_url  = $data['stream_url'];
$license_key = isset($data['license_key']) ? $data['license_key'] : '';

// Buat M3U output
header('Content-Type: application/vnd.apple.mpegurl; charset=utf-8');
header('Content-Disposition: inline; filename="inews.m3u"');

// Kamu bisa menambahkan header cache-control singkat agar client selalu minta ulang
header('Cache-Control: no-cache, must-revalidate, max-age=0');

echo "#EXTM3U\n";
echo "#EXTINF:-1 tvg-id=\"inews.id\" tvg-logo=\"https://github.com/ewinz19/xtskltshdt/blob/main/icons/inews.png?raw=true\" group-title=\"MY TV\",inews\n";
echo "#KODIPROP:inputstream=inputstream.adaptive\n";
echo "#KODIPROP:inputstreamaddon=inputstream.adaptive\n";
if ($license_key !== '') {
    // Sisipkan license key jika tersedia
    echo "#KODIPROP:inputstream.adaptive.license_type=clearkey\n";
    echo "#KODIPROP:inputstream.adaptive.license_key=" . trim($license_key) . "\n";
}
echo "#KODIPROP:inputstream.adaptive.manifest_type=hmac\n";
echo "#EXTVLCOPT:http-user-agent=iTunes-AppleTV/5.0.2 (3; dt:12)\n";
echo "####\n";
// Jika stream_url perlu header referer di m3u, tambahkan setelah pipe
echo $stream_url . "|Referer=https://icdn.rctiplus.id/\n";
