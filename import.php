<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '-1');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_STREAM'])) {
    header('Content-Type: text/plain');
    header('Cache-Control: no-cache');
    ob_implicit_flush(true);

    $dbHost  = trim($_POST['db_host']);
    $dbName  = trim($_POST['db_name']);
    $dbUser  = trim($_POST['db_user']);
    $dbPass  = trim($_POST['db_pass']);
    $sqlFile = trim($_POST['sql_file']);

    if ($dbHost === '' || $dbName === '' || $dbUser === '' || $sqlFile === '') {
        echo "ERROR: Semua field wajib diisi\n"; exit;
    }
    if (!file_exists($sqlFile)) {
        echo "ERROR: File SQL tidak ditemukan: $sqlFile\n"; exit;
    }
    if (!is_readable($sqlFile)) {
        echo "ERROR: File SQL tidak bisa dibaca (permission)\n"; exit;
    }

    echo "Start import database...\n";
    echo date('Y-m-d H:i:s') . "\n\n";

    $fileSize = filesize($sqlFile);
    $handle   = fopen($sqlFile, 'r');
    if (!$handle) {
        echo "ERROR: Gagal membuka file SQL\n"; exit;
    }

    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($mysqli->connect_error) {
        echo "Koneksi gagal: " . $mysqli->connect_error . "\n"; exit;
    }
    $mysqli->autocommit(false);

    $lineCount = 0;
    $chunkSize = 5000;
    $queries   = '';

    while (!feof($handle)) {
        $line = fgets($handle);
        if ($line === false) continue;
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '--') === 0 || strpos($trimmed, '#') === 0) {
            continue;
        }
        $queries .= $line;
        if (substr($trimmed, -1) === ';') {
            $lineCount++;
            if ($lineCount % $chunkSize === 0) {
                if (!$mysqli->multi_query($queries)) {
                    $mysqli->rollback();
                    echo "\nMYSQL ERROR: " . $mysqli->error . "\n"; exit;
                }
                do { $mysqli->store_result(); } while ($mysqli->more_results() && $mysqli->next_result());
                $queries = '';
                $progress = ftell($handle) / $fileSize * 100;
                echo "PROGRESS:" . round($progress, 2) . "\n";
            }
        }
    }

    if (trim($queries) !== '') {
        if (!$mysqli->multi_query($queries)) {
            $mysqli->rollback();
            echo "\nMYSQL ERROR: " . $mysqli->error . "\n"; exit;
        }
        do { $mysqli->store_result(); } while ($mysqli->more_results() && $mysqli->next_result());
    }

    $mysqli->commit();
    $mysqli->close();
    fclose($handle);

    echo "PROGRESS:100\n";
    echo "\nImport BERHASIL\n";
    echo date('Y-m-d H:i:s') . "\n";
    echo "COMPLETE:1\n"; 
    exit;
}