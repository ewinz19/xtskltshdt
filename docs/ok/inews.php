Bagus — aku buatkan contoh implementasi PHP yang aman secara konsep:

tes.php adalah endpoint (pada example.xom) yang hanya meng-return stream_url dan/atau license_key (mis. dari penyedia resmi).

playlist.php berada di server publik yang dipanggil Kodi; playlist.php akan mem-fetch data dari tes.php lalu menghasilkan M3U / KODI entry yang valid dengan token/key terbaru.


Penting — baca dulu: skrip ini tidak mengajarkan cara membypass token atau cara “mengambil” token dari layanan tanpa izin. Pastikan kamu punya hak / izin resmi untuk mengedarkan stream tersebut. Amankan tes.php (auth, IP whitelist, HTTPS) karena ia menyimpan kredensial sensitif.

Contoh 1 — tes.php (server yang menyimpan / mengeluarkan token)

Letakkan file ini di https://example.xom/tes.php. Contoh mengembalikan JSON:

<?php
// tes.php
// Harus diakses via HTTPS. JANGAN biarkan endpoint ini terbuka tanpa otorisasi.
// Contoh cara sederhana untuk menambahkan token sederhana: ?key=SECRETPASS

$secret = 'SECRETPASS'; // ganti dengan secret yang kuat
if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// Contoh respons: provider resmi memberi stream_url dan license_key.
// Di dunia nyata server ini memanggil API resmi penyedia atau menyimpan kredensial yang sah.
$response = [
    // contoh value; di produksi, dapatkan ini dari API resmi penyedia
    'stream_url'   => 'https://icdn.rctiplus.id/inews-sdi.m3u8?hdnts=exp=1762730372~hmac=b5f518ef8b578544b44b8e7217abc33aeee0da88965723883b55398cc30acfc3',
    'license_key'  => '53a4f8471e1843491926f29a92d40037418ca6abff76e928ab9fc5d9a26ace5b'
];

// Set header JSON dan kasih cache-control pendek
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=30'); // mis. cache 30 detik
echo json_encode($response);

Contoh 2 — playlist.php (membuat M3U dinamis untuk Kodi)

Letakkan file ini di server publik yang akan diakses Kodi; file ini memanggil tes.php (pakai secret) lalu membentuk output M3U/KODI:

<?php
// playlist.php
// URL contohnya: https://yourserver.com/playlist.php
// Pastikan menggunakan HTTPS

// Konfigurasi: endpoint yang aman (tes.php) dan secret
$tes_endpoint = 'https://example.xom/tes.php';
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

Cara kerja & cara pakai singkat

1. Deploy tes.php di https://example.xom/tes.php. Amankan endpoint (gunakan secret / IP whitelist / token auth).


2. Deploy playlist.php di server publik (mis. https://yourserver.com/playlist.php). Kodi akan memanggil playlist.php sebagai M3U.


3. playlist.php mem-fetch tes.php setiap kali di-request sehingga selalu mendapatkan stream_url / license_key yang baru menurut tes.php.


4. Jangan simpan credential di tempat publik. Gunakan HTTPS.



Keamanan & best practices

tes.php harus tidak bisa diakses publik tanpa otorisasi. Gunakan header auth, token, atau IP whitelist.

Batasi Cache-Control supaya token yang kadaluarsa tidak dipakai terlalu lama.

Jangan log license_key/stream_url di tempat yang mudah diakses.

Jika provider memberikan API, gunakan API resmi (mengambil token di server side) — ini adalah cara yang paling benar.

Pertimbangkan limit rate / retry handling bila API resmi kadang lambat.



---

Kalau mau, aku bisa:

Modifikasi contoh untuk autentikasi yang lebih aman (API key di header, Basic Auth, atau token JWT).

Buatkan versi yang me-proxy stream (server mendownload segmen dan meneruskan ke client) — catatan: ini lebih berat secara bandwidth dan memiliki implikasi legal.


Kamu mau lanjut ke mana?
