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
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>iDBase - Import Database SQL</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root {
    --bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --card: rgba(255, 255, 255, 0.95);
    --text: #2d3748;
    --primary: #667eea;
    --primary-dark: #5a67d8;
    --success: #48bb78;
    --error: #f56565;
    --warning: #ed8936;
    --info: #4299e1;
    --shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    --radius: 16px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

body {
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    position: relative;
    overflow-x: hidden;
}

body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    z-index: -1;
}

.container {
    width: 100%;
    max-width: 1200px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    align-items: start;
}

.card {
    background: var(--card);
    border-radius: var(--radius);
    padding: 40px;
    box-shadow: var(--shadow);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: var(--transition);
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.header {
    text-align: center;
    margin-bottom: 30px;
}

.header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 10px;
}

.header p {
    color: #718096;
    font-size: 1.1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #f7fafc, #edf2f7);
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid #e2e8f0;
    transition: var(--transition);
}

.stat-card:hover {
    background: linear-gradient(135deg, #edf2f7, #e2e8f0);
    transform: translateY(-2px);
}

.stat-card i {
    font-size: 2rem;
    color: var(--primary);
    margin-bottom: 10px;
}

.stat-card h3 {
    font-size: 1.5rem;
    color: var(--text);
    margin-bottom: 5px;
}

.stat-card p {
    color: #718096;
    font-size: 0.9rem;
}

.form-section h3 {
    font-size: 1.5rem;
    margin-bottom: 20px;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section h3 i {
    color: var(--primary);
}

.field {
    margin-bottom: 20px;
}

label {
    display: block;
    font-size: 0.9rem;
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

label i {
    color: var(--primary);
    width: 16px;
}

input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 1rem;
    transition: var(--transition);
    background: #f7fafc;
}

input:focus {
    outline: none;
    border-color: var(--primary);
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.file-input-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
    width: 100%;
}

.file-input-wrapper input[type=file] {
    position: absolute;
    left: -9999px;
}

.file-input-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px 16px;
    background: #f7fafc;
    border: 2px dashed #cbd5e0;
    border-radius: 10px;
    cursor: pointer;
    transition: var(--transition);
    color: #718096;
}

.file-input-button:hover {
    border-color: var(--primary);
    background: #edf2f7;
}

button {
    width: 100%;
    padding: 14px 24px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 10px;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

button:active {
    transform: translateY(0);
}

button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.info-box {
    background: linear-gradient(135deg, #ebf8ff, #bee3f8);
    border: 1px solid #90cdf4;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.info-box i {
    color: var(--info);
    font-size: 1.2rem;
    margin-top: 2px;
}

.info-box p {
    color: #2b6cb0;
    font-size: 0.9rem;
    line-height: 1.5;
}

.overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.overlay.show {
    display: flex;
}

.loader {
    background: white;
    border-radius: var(--radius);
    padding: 40px;
    width: 90%;
    max-width: 500px;
    box-shadow: var(--shadow);
    position: relative;
}

.loader-header {
    text-align: center;
    margin-bottom: 30px;
}

.loader-header h3 {
    font-size: 1.5rem;
    color: var(--text);
    margin-bottom: 10px;
}

.loader-header p {
    color: #718096;
    font-size: 0.9rem;
}

.progress-container {
    margin-bottom: 30px;
}

.progress-bar {
    height: 12px;
    background: #e2e8f0;
    border-radius: 6px;
    overflow: hidden;
    position: relative;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), var(--primary-dark));
    border-radius: 6px;
    transition: width 0.3s ease;
    position: relative;
}

.progress-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.progress-text {
    text-align: center;
    margin-top: 10px;
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--primary);
}

.status {
    padding: 16px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.status.loading {
    background: linear-gradient(135deg, #ebf8ff, #bee3f8);
    border: 1px solid #90cdf4;
    color: #2b6cb0;
}

.status.success {
    background: linear-gradient(135deg, #f0fff4, #c6f6d5);
    border: 1px solid #9ae6b4;
    color: #22543d;
}

.status.error {
    background: linear-gradient(135deg, #fed7d7, #feb2b2);
    border: 1px solid #fc8181;
    color: #742a2a;
}

.status-icon {
    font-size: 1.5rem;
}

.status-content {
    flex: 1;
}

.status-content h4 {
    font-size: 1.1rem;
    margin-bottom: 4px;
}

.status-content p {
    font-size: 0.9rem;
    opacity: 0.8;
}

.close-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: rgba(0, 0, 0, 0.1);
    color: inherit;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
}

.close-btn:hover {
    background: rgba(0, 0, 0, 0.2);
}

.log-container {
    background: #f7fafc;
    border-radius: 10px;
    padding: 20px;
    max-height: 200px;
    overflow-y: auto;
}

.log-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.log-header h5 {
    font-size: 1rem;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 8px;
}

.log-content {
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 0.85rem;
    line-height: 1.5;
    color: #4a5568;
    white-space: pre-wrap;
}

.log-entry {
    margin-bottom: 8px;
    padding: 4px 0;
    border-left: 3px solid transparent;
    padding-left: 12px;
}

.log-entry.info {
    border-left-color: var(--info);
}

.log-entry.success {
    border-left-color: var(--success);
}

.log-entry.error {
    border-left-color: var(--error);
}

.log-entry.warning {
    border-left-color: var(--warning);
}

.features {
    margin-top: 30px;
}

.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px;
    background: #f7fafc;
    border-radius: 10px;
    transition: var(--transition);
}

.feature-item:hover {
    background: #edf2f7;
    transform: translateX(5px);
}

.feature-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.feature-content h4 {
    font-size: 1.1rem;
    margin-bottom: 5px;
    color: var(--text);
}

.feature-content p {
    color: #718096;
    font-size: 0.9rem;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .container {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .card {
        padding: 25px;
    }
    
    .header h1 {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="header">
            <h1><i class="fas fa-database"></i> iDBase</h1>
            <p>Import Database SQL dengan Mudah dan Cepat</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-tachometer-alt"></i>
                <h3>Cepat</h3>
                <p>Import besar tanpa timeout</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-shield-alt"></i>
                <h3>Aman</h3>
                <p>Transaksi & rollback otomatis</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-chart-line"></i>
                <h3>Real-time</h3>
                <p>Progress monitoring langsung</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-file-code"></i>
                <h3>Support</h3>
                <p>Semua format SQL</p>
            </div>
        </div>

        <div class="features">
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <div class="feature-content">
                    <h4>Performa Optimal</h4>
                    <p>Chunk processing 5000 query per batch untuk menghindari memory overflow</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="feature-content">
                    <h4>Keamanan Terjamin</h4>
                    <p>Rollback otomatis jika terjadi error, data Anda aman 100%</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="feature-content">
                    <h4>Monitoring Real-time</h4>
                    <p>Lihat progress import, kecepatan, dan estimasi waktu selesai</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="form-section">
            <h3><i class="fas fa-upload"></i> Import Database</h3>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <div>
                    <p><strong>Petunjuk:</strong> Pastikan file SQL Anda valid, file .SQL sudah diupload ke hosting dan database target sudah tersedia. Proses import akan otomatis rollback jika terjadi error.</p>
                </div>
            </div>

            <form id="importForm">
                <div class="field">
                    <label><i class="fas fa-server"></i> Host Database</label>
                    <input name="db_host" placeholder="contoh: localhost" required>
                </div>
                <div class="field">
                    <label><i class="fas fa-database"></i> Nama Database</label>
                    <input name="db_name" placeholder="contoh: my_database" required>
                </div>
                <div class="field">
                    <label><i class="fas fa-user"></i> Username Database</label>
                    <input name="db_user" placeholder="contoh: root" required>
                </div>
                <div class="field">
                    <label><i class="fas fa-key"></i> Password</label>
                    <input type="password" name="db_pass" placeholder="Kosongkan jika tidak ada">
                </div>
                <div class="field">
                    <label><i class="fas fa-file"></i> File SQL</label>
                    <input name="sql_file" placeholder="Contoh: /home/user/file.sql" required>
                </div>
                <button type="submit">
                    <i class="fas fa-play"></i> Mulai Import
                </button>
            </form>
        </div>
    </div>
</div>

<div class="overlay" id="overlay">
    <div class="loader">
        <div class="loader-header">
            <h3><i class="fas fa-cog fa-spin"></i> Sedang Mengimport</h3>
            <p>Mohon tunggu, proses import sedang berlangsung...</p>
        </div>

        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill" id="barInner"></div>
            </div>
            <div class="progress-text" id="percent">0%</div>
        </div>

        <div id="status" class="status loading">
            <div class="status-content">
                <div class="status-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h4>Memproses Database</h4>
                    <p>Menjalankan query SQL...</p>
                </div>
            </div>
        </div>

        <div class="log-container">
            <div class="log-header">
                <h5><i class="fas fa-list"></i> Log Proses</h5>
                <!--<button class="close-btn" onclick="closeOverlay()" title="Tutup">
                    <i class="fas fa-times"></i>
                </button>-->
            </div>
            <div class="log-content" id="log"></div>
        </div>
    </div>
</div>

<script>
const form = document.getElementById('importForm');
const overlay = document.getElementById('overlay');
const bar = document.getElementById('barInner');
const percent = document.getElementById('percent');
const log = document.getElementById('log');
const statusBox = document.getElementById('status');

function addLog(message, type = 'info') {
    const entry = document.createElement('div');
    entry.className = `log-entry ${type}`;
    entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
    log.appendChild(entry);
    log.scrollTop = log.scrollHeight;
}

function updateStatus(type, title, message, icon) {
    statusBox.className = `status ${type}`;
    statusBox.innerHTML = `
        <div class="status-content">
            <div class="status-icon">
                <i class="${icon}"></i>
            </div>
            <div>
                <h4>${title}</h4>
                <p>${message}</p>
            </div>
        </div>
        ${type === 'success'
            ? `<button class="close-btn" onclick="closeOverlay()" title="Tutup">
                    <i class="fas fa-times"></i>
               </button>`
            : ''
        }
    `;
}

function closeOverlay() {
    overlay.classList.remove('show');
    setTimeout(() => location.reload(), 500);
}

form.addEventListener('submit', async e => {
    e.preventDefault();
    overlay.classList.add('show');

    log.innerHTML = '';
    bar.style.width = '0%';
    percent.textContent = '0%';

    updateStatus(
        'loading',
        'Memulai Import',
        'Menghubungkan ke database...',
        'fas fa-play'
    );

    const formData = new FormData(form);

    try {
        const res = await fetch('', {
            method: 'POST',
            headers: { 'X-Stream': '1' },
            body: formData
        });

        if (!res.ok) {
            throw new Error(`HTTP error ${res.status}`);
        }

        const reader = res.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop();

            for (const raw of lines) {
                const line = raw.trim();
                if (!line) continue;
=
                if (line.startsWith('PROGRESS:')) {
                    const progress = parseFloat(
                        line.replace('PROGRESS:', '').trim()
                    );

                    if (!isNaN(progress)) {
                        bar.style.width = `${progress}%`;
                        percent.textContent = `${Math.round(progress)}%`;

                        if (progress < 30) {
                            updateStatus(
                                'loading',
                                'Memproses Awal',
                                'Membaca file SQL...',
                                'fas fa-file-alt'
                            );
                        } else if (progress < 70) {
                            updateStatus(
                                'loading',
                                'Sedang Berjalan',
                                'Menjalankan query...',
                                'fas fa-cogs'
                            );
                        } else if (progress < 100) {
                            updateStatus(
                                'loading',
                                'Hampir Selesai',
                                'Finalisasi import...',
                                'fas fa-hourglass-half'
                            );
                        }
                    }
                }

                else if (line.startsWith('INFO:')) {
                    addLog(line.replace('INFO:', '').trim(), 'info');
                }

                else if (line.startsWith('SUCCESS:')) {
                    addLog(line.replace('SUCCESS:', '').trim(), 'success');
                    updateStatus(
                        'success',
                        'Import Berhasil!',
                        'Database berhasil diimport',
                        'fas fa-check-circle'
                    );
                }

                else if (
                    line.startsWith('ERROR:') ||
                    line.startsWith('MYSQL ERROR:')
                ) {
                    const msg = line
                        .replace('MYSQL ERROR:', '')
                        .replace('ERROR:', '')
                        .trim();

                    addLog(msg, 'error');
                    updateStatus(
                        'error',
                        'Import Gagal!',
                        msg,
                        'fas fa-exclamation-triangle'
                    );
                }

                else if (line.includes('COMPLETE:1')) {
                    addLog('Import selesai', 'success');
                    updateStatus(
                        'success',
                        'Import Selesai!',
                        'Semua data berhasil diimport',
                        'fas fa-check-double'
                    );
                }

                else {
                    addLog(line, 'info');
                }
            }
        }

    } catch (err) {
        console.error(err);
        addLog(err.message, 'error');
        updateStatus(
            'error',
            'Koneksi Error!',
            'Gagal terhubung ke server',
            'fas fa-plug'
        );
    }
});
</script>

</body>
</html>
