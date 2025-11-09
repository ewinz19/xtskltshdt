<?php
header('Content-Type: application/vnd.apple.mpegurl; charset=utf-8');
header('Content-Disposition: inline; filename="inews.m3u"');
header('Cache-Control: no-cache, must-revalidate, max-age=0');

// Base64 dari JS
$encoded_url = 'aHR0cHM6Ly9pY2RuLnJjdGlwbHVzLmlkL2luZXdzLXNkaS5tM3U4P2hkbnRzPWV4cD0xNzYyNzM3MDA0fmhtYWM9MGJjYzdiZGRhMzJiM2YzNjhlMDc0MTcxMzBmMzY2MDY2OWZjYTc5NjNlZDE2OWQ3N2E3ODAzOWNkOTFjYzRhZQ==';
$stream_url = base64_decode($encoded_url);

// M3U
$logo = 'https://github.com/ewinz19/xtskltshdt/blob/main/icons/inews.png?raw=true';
$group = 'MY TV';
$title = 'inews';

echo "#EXTM3U\n";
echo "#EXTINF:-1 tvg-id=\"inews.id\" tvg-logo=\"{$logo}\" group-title=\"{$group}\",{$title}\n";
echo "#EXTVLCOPT:http-user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0 Safari/537.36\n";
echo "#EXTVLCOPT:http-referrer=https://embed.rctiplus.com/\n";
echo $stream_url . "\n";
