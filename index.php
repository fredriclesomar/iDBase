<?php
/**
 * © 2026 Fredric Lesomar
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

require 'import.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>iDBase - Import Database SQL</title>
<link rel="icon" type="image/x-icon" href="./gambar/database.ico">
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
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

<script src="assets/js/app.js"></script>
</body>
</html>
