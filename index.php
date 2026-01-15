<?php
$dbHost = 'localhost';
$dbName = 'namadb';
$dbUser = 'userdb';
$dbPass = 'passworddb';

$sqlFile = '/home/user/public_html/database/contoh.sql';

if (!file_exists($sqlFile)) {
    echo "ERROR: File SQL tidak ditemukan\n";
    exit(1);
}

echo "Start import database...\n";
echo date('Y-m-d H:i:s') . "\n";

$fileSize = filesize($sqlFile);
$handle = fopen($sqlFile, 'r');
if (!$handle) {
    echo "Gagal membuka file SQL\n";
    exit(1);
}

$lineCount = 0;
$chunkSize = 5000; // jumlah query per batch
$queries = '';
$progress = 0;

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

$mysqli->autocommit(false);

while (!feof($handle)) {
    $line = fgets($handle);
    if ($line === false) continue;
    
    $trimmed = trim($line);
    if ($trimmed === '' || strpos($trimmed, '--') === 0 || strpos($trimmed, '#') === 0) {
        continue;
    }

    $queries .= $line;
    if (substr(trim($line), -1) === ';') {
        if (++$lineCount % $chunkSize === 0) {
            if (!$mysqli->multi_query($queries)) {
                echo "\nError: " . $mysqli->error . "\n";
                exit(1);
            }
            do { $mysqli->store_result(); } while ($mysqli->more_results() && $mysqli->next_result());
            $queries = '';
            
            $progress = ftell($handle) / $fileSize * 100;
            echo "\rProgress: " . round($progress, 2) . "%";
        }
    }
}

if (trim($queries) !== '') {
    if (!$mysqli->multi_query($queries)) {
        echo "\nError: " . $mysqli->error . "\n";
        exit(1);
    }
    do { $mysqli->store_result(); } while ($mysqli->more_results() && $mysqli->next_result());
    echo "\rProgress: 100%\n";
}

$mysqli->commit();
$mysqli->close();
fclose($handle);

echo "\nImport BERHASIL\n";
echo date('Y-m-d H:i:s') . "\n";
