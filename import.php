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
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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
        'db_host'  => $fredric_host,
        'db_name'  => $fredric_name,
        'db_user'  => $lesomar_user,
        'sql_file' => $fredric_sql,
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
        echo "ERROR: File SQL tidak ditemukan\n";
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

    try {

        $lesomar_file_size = filesize($fredric_sql);
        $lesomar_handle = fopen($fredric_sql, 'r');
        if (!$lesomar_handle) {
            throw new Exception('Gagal membuka file SQL');
        }

        $fredric_mysqli = new mysqli(
            $fredric_host,
            $lesomar_user,
            $lesomar_pass,
            $fredric_name
        );

        $fredric_mysqli->autocommit(false);

        $lesomar_line_count  = 0;
        $fredric_chunk_size = 5000;
        $lesomar_queries    = '';

        while (!feof($lesomar_handle)) {

            $lesomar_line = fgets($lesomar_handle);
            if ($lesomar_line === false) continue;

            $fredric_trimmed = trim($lesomar_line);
            if (
                $fredric_trimmed === '' ||
                strpos($fredric_trimmed, '--') === 0 ||
                strpos($fredric_trimmed, '#') === 0
            ) {
                continue;
            }

            $lesomar_queries .= $lesomar_line;

            if (substr($fredric_trimmed, -1) === ';') {
                $lesomar_line_count++;

                if ($lesomar_line_count % $fredric_chunk_size === 0) {

                    $fredric_mysqli->multi_query($lesomar_queries);

                    do {
                        if ($result = $fredric_mysqli->store_result()) {
                            $result->free();
                        }
                    } while ($fredric_mysqli->more_results() && $fredric_mysqli->next_result());

                    $lesomar_queries = '';

                    $fredric_progress = (ftell($lesomar_handle) / $lesomar_file_size) * 100;
                    echo "PROGRESS:" . round($fredric_progress, 2) . "\n";
                }
            }
        }

        if (trim($lesomar_queries) !== '') {
            $fredric_mysqli->multi_query($lesomar_queries);

            do {
                if ($result = $fredric_mysqli->store_result()) {
                    $result->free();
                }
            } while ($fredric_mysqli->more_results() && $fredric_mysqli->next_result());
        }

        $fredric_mysqli->commit();
        fclose($lesomar_handle);
        $fredric_mysqli->close();

        saveImportHistory($historyBase + [
            'status'  => 'success',
            'message' => 'Import database berhasil'
        ]);

        echo "PROGRESS:100\n";
        echo "Import BERHASIL\n";
        echo date('Y-m-d H:i:s') . "\n";
        echo "COMPLETE:1\n";
        exit;

    } catch (mysqli_sql_exception $e) {

        if (isset($fredric_mysqli)) {
            $fredric_mysqli->rollback();
        }

        $code = $e->getCode();
        $msg  = $e->getMessage();

        if (stripos($msg, 'already exists') !== false) {
            $msg = 'Tabel sudah ada';
        }

        saveImportHistory($historyBase + [
            'status'  => 'failed',
            'message' => "SQL $code: $msg"
        ]);

        echo "ERROR (SQL $code): $msg\n";
        echo "Import dibatalkan & rollback dilakukan\n";
        exit;

    } catch (Exception $e) {

        saveImportHistory($historyBase + [
            'status'  => 'failed',
            'message' => $e->getMessage()
        ]);

        echo "ERROR: " . $e->getMessage() . "\n";
        exit;
    }
}

