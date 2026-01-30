<?php
/**
 * iDBase - Import Database SQL
 * © 2026 Fredric Lesomar
 *
 * This file is part of iDBase.
 *
 * iDBase is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE file for details.
 */

function saveImportHistory(array $data)
{
    $file = __DIR__ . '/data/import_history.json';

    if (!file_exists($file)) {
        file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
    }

    $history = json_decode(file_get_contents($file), true);
    if (!is_array($history)) {
        $history = [];
    }

    $history[] = $data;
    $history = array_slice($history, -200);

    file_put_contents(
        $file,
        json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '-1');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_STREAM'])) {
    header('Content-Type: text/plain');
    header('Cache-Control: no-cache');
    ob_implicit_flush(true);

    $fredric_host = trim($_POST['db_host']);
    $fredric_name = trim($_POST['db_name']);
    $lesomar_user = trim($_POST['db_user']);
    $lesomar_pass = trim($_POST['db_pass']);
    $fredric_sql  = trim($_POST['sql_file']);

    $historyBase = [
        'time'     => date('Y-m-d H:i:s'),
        'db_host' => $fredric_host,
        'db_name' => $fredric_name,
        'db_user' => $lesomar_user,
        'sql_file'=> $fredric_sql,
    ];

    if ($fredric_host === '' || $fredric_name === '' || $lesomar_user === '' || $fredric_sql === '') {
        saveImportHistory($historyBase + [
            'status'  => 'failed',
            'message' => 'Semua field wajib diisi'
        ]);
        echo "ERROR: Semua field wajib diisi\n";
        exit;
    }

    if (!file_exists($fredric_sql)) {
        saveImportHistory($historyBase + [
            'status'  => 'failed',
            'message' => 'File SQL tidak ditemukan'
        ]);
        echo "ERROR: File SQL tidak ditemukan: $fredric_sql\n";
        exit;
    }

    if (!is_readable($fredric_sql)) {
        saveImportHistory($historyBase + [
            'status'  => 'failed',
            'message' => 'File SQL tidak bisa dibaca (permission)'
        ]);
        echo "ERROR: File SQL tidak bisa dibaca (permission)\n";
        exit;
    }

    echo "Start import database...\n";
    echo date('Y-m-d H:i:s') . "\n\n";

    $lesomar_file_size = filesize($fredric_sql);
    $lesomar_handle = fopen($fredric_sql, 'r');
    if (!$lesomar_handle) {
        saveImportHistory($historyBase + [
            'status'  => 'failed',
            'message' => 'Gagal membuka file SQL'
        ]);
        echo "ERROR: Gagal membuka file SQL\n";
        exit;
    }

    $fredric_mysqli = new mysqli($fredric_host, $lesomar_user, $lesomar_pass, $fredric_name);
    if ($fredric_mysqli->connect_error) {
        saveImportHistory($historyBase + [
            'status'  => 'failed',
            'message' => $fredric_mysqli->connect_error
        ]);
        echo "Koneksi gagal: " . $fredric_mysqli->connect_error . "\n";
        exit;
    }

    $fredric_mysqli->autocommit(false);
    $lesomar_line_count  = 0;
    $fredric_chunk_size = 5000;
    $lesomar_queries    = '';

    while (!feof($lesomar_handle)) {
        $lesomar_line = fgets($lesomar_handle);
        if ($lesomar_line === false) continue;

        $fredric_trimmed = trim($lesomar_line);
        if ($fredric_trimmed === '' ||
            strpos($fredric_trimmed, '--') === 0 ||
            strpos($fredric_trimmed, '#') === 0) {
            continue;
        }

        $lesomar_queries .= $lesomar_line;

        if (substr($fredric_trimmed, -1) === ';') {
            $lesomar_line_count++;

            if ($lesomar_line_count % $fredric_chunk_size === 0) {
                if (!$fredric_mysqli->multi_query($lesomar_queries)) {
                    $fredric_mysqli->rollback();

                    saveImportHistory($historyBase + [
                        'status'  => 'failed',
                        'message' => $fredric_mysqli->error
                    ]);

                    echo "\nMYSQL ERROR: " . $fredric_mysqli->error . "\n";
                    exit;
                }

                do {
                    $fredric_mysqli->store_result();
                } while ($fredric_mysqli->more_results() && $fredric_mysqli->next_result());

                $lesomar_queries = '';
                $fredric_progress = ftell($lesomar_handle) / $lesomar_file_size * 100;
                echo "PROGRESS:" . round($fredric_progress, 2) . "\n";
            }
        }
    }

    if (trim($lesomar_queries) !== '') {
        if (!$fredric_mysqli->multi_query($lesomar_queries)) {
            $fredric_mysqli->rollback();

            saveImportHistory($historyBase + [
                'status'  => 'failed',
                'message' => $fredric_mysqli->error
            ]);

            echo "\nMYSQL ERROR: " . $fredric_mysqli->error . "\n";
            exit;
        }

        do {
            $fredric_mysqli->store_result();
        } while ($fredric_mysqli->more_results() && $fredric_mysqli->next_result());
    }

    $fredric_mysqli->commit();
    $fredric_mysqli->close();
    fclose($lesomar_handle);

    saveImportHistory($historyBase + [
        'status'  => 'success',
        'message' => 'Import database berhasil'
    ]);

    echo "PROGRESS:100\n";
    echo "\nImport BERHASIL\n";
    echo date('Y-m-d H:i:s') . "\n";
    echo "COMPLETE:1\n";
    exit;
}
