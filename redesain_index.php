<?php
/* ---------- BARIS PHP TETAP 100 % SAMA ---------- */
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '-1');

/* ---------- JIKA REQUEST VIA FETCH (streaming) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_STREAM'])) {
    header('Content-Type: text/plain');
    header('Cache-Control: no-cache');
    ob_implicit_flush(true); // langsung keluar tiap echo

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
                echo "PROGRESS:" . round($progress, 2) . "\n"; // tag khusus
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
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Import Database SQL</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{
    --bg:#f4f6fb;
    --card:#ffffff;
    --text:#111827;
    --primary:#4f46e5;
    --primary-dark:#3730a3;
    --radius:12px;
    --shadow:0 10px 30px rgba(0,0,0,.07);
}
@media (prefers-color-scheme:dark){
    :root{--bg:#111827;--card:#1f2937;--text:#f9fafb;}
}
*{box-sizing:border-box;margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial}
body{background:var(--bg);color:var(--text);display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px}
.card{width:100%;max-width:480px;background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);padding:36px}
h3{margin-bottom:28px;font-size:24px;letter-spacing:-.6px}
label{font-size:14px;margin-bottom:6px;display:block}
input[type=text],input[type=password]{width:100%;padding:12px 14px;border:1px solid #d1d5db;border-radius:var(--radius);background:var(--card);color:var(--text);font-size:15px;transition:.2s}
input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(79,70,229,.18)}
.field{margin-bottom:18px}
button{width:100%;padding:14px;border:none;border-radius:var(--radius);background:var(--primary);color:#fff;font-size:16px;font-weight:600;cursor:pointer;transition:.25s}
button:hover{background:var(--primary-dark)}

/* layar loading modern */
.overlay{
    position:fixed;inset:0;background:rgba(0,0,0,.65);
    display:none;align-items:center;justify-content:center;z-index:9999
}
.overlay.show{display:flex}
.loader{
    background:var(--card);border-radius:var(--radius);
    width:90%;max-width:400px;padding:32px;text-align:center
}
.loader h4{margin-bottom:20px;font-size:18px}
.bar{
    height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;margin-bottom:10px
}
.bar-inner{height:100%;background:var(--primary);width:0%;transition:width .3s ease}
.log{white-space:pre-wrap;text-align:left;font-size:13px;max-height:120px;overflow-y:auto;color:#6b7280;margin-top:12px}
</style>
</head>
<body>
<div class="card">
    <h3>Import Database SQL</h3>
    <form id="formImport">
        <div class="field">
            <label>DB Host</label>
            <input type="text" name="db_host" placeholder="localhost" required>
        </div>
        <div class="field">
            <label>Nama Database</label>
            <input type="text" name="db_name" placeholder="nama_database" required>
        </div>
        <div class="field">
            <label>User Database</label>
            <input type="text" name="db_user" placeholder="root" required>
        </div>
        <div class="field">
            <label>Password Database</label>
            <input type="password" name="db_pass" placeholder="Kosongkan jika tidak ada">
        </div>
        <div class="field">
            <label>Path File SQL</label>
            <input type="text" name="sql_file" placeholder="/home/user/public_html/db.sql" required>
        </div>
        <button type="submit">Import Database</button>
    </form>
</div>

<!-- Loading Overlay -->
<div class="overlay" id="overlay">
    <div class="loader">
        <h4>Mengimport Database...</h4>
        <div class="bar"><div class="bar-inner" id="barInner"></div></div>
        <div id="percent">0%</div>
        <div class="log" id="log">Menghubungkan...</div>
    </div>
</div>

<script>
const form = document.getElementById('formImport');
const overlay = document.getElementById('overlay');
const barInner = document.getElementById('barInner');
const percent = document.getElementById('percent');
const log = document.getElementById('log');

form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    overlay.classList.add('show');

    const params = new FormData(form);
    const res = await fetch('',{
        method:'POST',
        headers:{'X-Stream':'1'},
        body:params
    });

    const reader = res.body.getReader();
    const dec = new TextDecoder();
    let buf = '';

    while(true){
        const {done,value} = await reader.read();
        if(done) break;
        buf += dec.decode(value,{stream:true});

        let lines = buf.split('\n');
        buf = lines.pop(); // sisa setengah baris

        for(const raw of lines){
            const line = raw.trim();
            if(line.startsWith('PROGRESS:')){
                const num = parseFloat(line.replace('PROGRESS:',''));
                barInner.style.width = num + '%';
                percent.textContent = Math.round(num) + '%';
            }else{
                log.textContent += raw + '\n';
                log.scrollTop = log.scrollHeight;
            }
        }
    }

    // selesai
    barInner.style.width = '100%';
    percent.textContent = '100%';
    log.textContent += '\nSelesai! Menutup otomatis...';
    setTimeout(()=>location.reload(),1800);
});
</script>
</body>
</html>
