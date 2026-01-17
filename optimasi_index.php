<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '-1');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dbHost  = trim($_POST['db_host']);
    $dbName  = trim($_POST['db_name']);
    $dbUser  = trim($_POST['db_user']);
    $dbPass  = trim($_POST['db_pass']);
    $sqlFile = trim($_POST['sql_file']);

    echo "<pre>";

    if ($dbHost === '' || $dbName === '' || $dbUser === '' || $sqlFile === '') {
        die("ERROR: Semua field wajib diisi\n");
    }

    if (!file_exists($sqlFile)) {
        die("ERROR: File SQL tidak ditemukan: $sqlFile\n");
    }

    if (!is_readable($sqlFile)) {
        die("ERROR: File SQL tidak bisa dibaca (permission)\n");
    }

    echo "Start import database...\n";
    echo date('Y-m-d H:i:s') . "\n\n";

    $fileSize = filesize($sqlFile);
    $handle = fopen($sqlFile, 'r');
    if (!$handle) {
        die("ERROR: Gagal membuka file SQL\n");
    }

    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($mysqli->connect_error) {
        die("Koneksi gagal: " . $mysqli->connect_error . "\n");
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
                    die("\nMYSQL ERROR: " . $mysqli->error . "\n");
                }

                do {
                    $mysqli->store_result();
                } while ($mysqli->more_results() && $mysqli->next_result());

                $queries = '';

                $progress = ftell($handle) / $fileSize * 100;
                echo "\rProgress: " . round($progress, 2) . "%";
            }
        }
    }

    if (trim($queries) !== '') {
        if (!$mysqli->multi_query($queries)) {
            $mysqli->rollback();
            die("\nMYSQL ERROR: " . $mysqli->error . "\n");
        }
        do {
            $mysqli->store_result();
        } while ($mysqli->more_results() && $mysqli->next_result());
    }

    $mysqli->commit();
    $mysqli->close();
    fclose($handle);

    echo "\rProgress: 100%\n";
    echo "\nImport BERHASIL\n";
    echo date('Y-m-d H:i:s') . "\n";
    echo "</pre>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Import Database SQL (Path)</title>
    <style>
        body { font-family: Arial; background:#f4f4f4; }
        .box {
            width: 500px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 6px;
        }
        input, button {
            width: 100%;
            padding: 8px;
            margin: 6px 0;
        }
    </style>
</head>
<body>

<div class="box">
    <h3>Import Database dari File SQL (Server)</h3>
    <form method="post">
        <input type="text" name="db_host" placeholder="DB Host (localhost)" required>
        <input type="text" name="db_name" placeholder="Nama Database" required>
        <input type="text" name="db_user" placeholder="User Database" required>
        <input type="password" name="db_pass" placeholder="Password Database">
        <input type="text" name="sql_file" placeholder="/home/user/public_html/database/contoh.sql" required>
        <button type="submit">Import Database</button>
    </form>
</div>

</body>
</html>
