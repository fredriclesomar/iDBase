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

require_once __DIR__ . '/global/config.php';

$file = __DIR__ . '/data/import_history.json';
$history = [];

if (file_exists($file)) {
    $history = json_decode(file_get_contents($file), true);
    if (!is_array($history)) {
        $history = [];
    }
}

$history = array_reverse($history);

$total = count($history);
$success = count(array_filter($history, fn($h) => ($h['status'] ?? '') === 'success'));
$failed = $total - $success;
$success_rate = $total > 0 ? round(($success / $total) * 100) : 0;

$databases = array_unique(array_column($history, 'db_name'));
sort($databases);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Import History - iDBase</title>
<link rel="icon" type="image/x-icon" href="./gambar/database.ico">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>
<nav class="nav-menu">
    <div class="nav-container">
        <a href="index" class="nav-brand">
            <i class="fas fa-database"></i>
            <span>iDBase</span>
        </a>
        <ul class="nav-links">
            <li>
                <a href="index">
                    <i class="fas fa-home"></i>
                    <span>Import</span>
                </a>
            </li>
            <li>
                <a href="history" class="active">
                    <i class="fas fa-history"></i>
                    <span>History</span>
                </a>
            </li>
        </ul>
    </div>
</nav>

<div class="container history-container">
    <div class="card">
        <div class="header" style="text-align: left; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px;">
            <h1><i class="fas fa-history"></i> Import History</h1>
            <p>Riwayat dan statistik import database Anda</p>
        </div>

        <?php if (empty($history)): ?>
            
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Belum Ada History</h3>
                <p>Anda belum melakukan import database. Mulai import pertama Anda dari halaman utama.</p>
                <br>
                <a href="index" class="btn-action btn-primary">
                    <i class="fas fa-plus"></i> Import Baru
                </a>
            </div>

        <?php else: ?>

            <div class="stats-overview">
                <div class="stat-box primary">
                    <div class="stat-icon">
                        <i class="fas fa-list-ol"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="stat-total"><?= $total ?></h3>
                        <p>Total Import</p>
                    </div>
                </div>
                <div class="stat-box success">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="stat-success"><?= $success ?></h3>
                        <p>Berhasil</p>
                    </div>
                </div>
                <div class="stat-box error">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="stat-failed"><?= $failed ?></h3>
                        <p>Gagal</p>
                    </div>
                </div>
                <div class="stat-box" style="background: linear-gradient(135deg, #fef5e7, #fdebd0); border-color: #f6e05e;">
                    <div class="stat-icon" style="color: #d69e2e;">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="stat-rate"><?= $success_rate ?>%</h3>
                        <p>Success Rate</p>
                    </div>
                </div>
            </div>

            <div class="controls">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari database, file, atau pesan...">
                </div>
                
                <div class="filters">
                    <select class="filter-select" id="statusFilter">
                        <option value="">Semua Status</option>
                        <option value="success">Berhasil</option>
                        <option value="failed">Gagal</option>
                    </select>
                    
                    <select class="filter-select" id="dbFilter">
                        <option value="">Semua Database</option>
                        <?php foreach ($databases as $db): ?>
                            <option value="<?= htmlspecialchars($db) ?>"><?= htmlspecialchars($db) ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button class="btn-action btn-secondary" onclick="exportHistory()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="history-table" id="historyTable">
                    <thead>
                        <tr>
                            <th class="sortable" onclick="sortTable(0)">Waktu <i class="fas fa-sort"></i></th>
                            <th class="sortable" onclick="sortTable(1)">Database <i class="fas fa-sort"></i></th>
                            <th class="sortable" onclick="sortTable(2)">User <i class="fas fa-sort"></i></th>
                            <th>File SQL</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php foreach ($history as $index => $h): 
                            $time = strtotime($h['time']);
                            $timeAgo = timeAgo($time);
                        ?>
                            <tr data-status="<?= $h['status'] ?>" data-db="<?= htmlspecialchars($h['db_name']) ?>" data-search="<?= strtolower($h['db_name'].' '.$h['db_user'].' '.basename($h['sql_file']).' '.$h['message']) ?>">
                                <td>
                                    <div class="timestamp">
                                        <span class="time-main"><?= date('d M Y H:i', $time) ?></span>
                                        <span class="time-ago"><?= $timeAgo ?></span>
                                    </div>
                                </td>
                                <td class="cell-db">
                                    <i class="fas fa-database" style="color: #cbd5e0; margin-right: 5px;"></i>
                                    <?= htmlspecialchars($h['db_name']) ?>
                                </td>
                                <td><?= htmlspecialchars($h['db_user']) ?></td>
                                <td class="cell-file">
                                    <i class="fas fa-file-code"></i>
                                    <span title="<?= htmlspecialchars($h['sql_file']) ?>">
                                        <?= htmlspecialchars(basename($h['sql_file'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($h['status'] === 'success'): ?>
                                        <span class="status-badge success">
                                            <i class="fas fa-check"></i> Berhasil
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge error">
                                            <i class="fas fa-times"></i> Gagal
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-message" title="<?= htmlspecialchars($h['message']) ?>">
                                    <?= htmlspecialchars($h['message']) ?>
                                </td>
                                <td class="cell-actions">
                                    <button class="btn-action btn-secondary btn-sm" onclick="viewDetail(<?= $index ?>)" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($h['status'] === 'error' && isset($h['sql_file'])): ?>
                                        <a href="index.php?retry=<?= urlencode($h['sql_file']) ?>&db=<?= urlencode($h['db_name']) ?>" 
                                           class="btn-action btn-primary btn-sm" title="Retry Import">
                                            <i class="fas fa-redo"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="pagination">
            </div>

        <?php endif; ?>
    </div>
</div>

<div class="modal-overlay" id="detailModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-info-circle"></i> Detail Import</h3>
            <button class="close-btn" onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #718096;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="modalBody">
        </div>
        <div class="modal-footer">
            <button class="btn-action btn-secondary" onclick="closeModal()">Tutup</button>
        </div>
    </div>
</div>

<script>
const historyData = <?= json_encode($history) ?>;
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const dbFilter = document.getElementById('dbFilter');
const tableBody = document.getElementById('tableBody');
const rows = tableBody.getElementsByTagName('tr');

function filterTable() {
    const searchTerm = searchInput.value.toLowerCase();
    const statusValue = statusFilter.value;
    const dbValue = dbFilter.value;
    
    let visibleCount = 0;
    
    for (let row of rows) {
        const searchData = row.getAttribute('data-search');
        const status = row.getAttribute('data-status');
        const db = row.getAttribute('data-db');
        
        const matchSearch = searchData.includes(searchTerm);
        const matchStatus = !statusValue || status === statusValue;
        const matchDb = !dbValue || db === dbValue;
        
        if (matchSearch && matchStatus && matchDb) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    }
    
    updateStats(visibleCount);
}

searchInput.addEventListener('input', filterTable);
statusFilter.addEventListener('change', filterTable);
dbFilter.addEventListener('change', filterTable);

let sortDirection = {};

function sortTable(columnIndex) {
    const tbody = document.getElementById('tableBody');
    const rows = Array.from(tbody.getElementsByTagName('tr'));
    
    sortDirection[columnIndex] = !sortDirection[columnIndex];
    
    rows.sort((a, b) => {
        let aVal = a.getElementsByTagName('td')[columnIndex].textContent.trim();
        let bVal = b.getElementsByTagName('td')[columnIndex].textContent.trim();
        
        if (columnIndex === 0) {
            return sortDirection[columnIndex] ? 
                new Date(bVal) - new Date(aVal) : 
                new Date(aVal) - new Date(bVal);
        }
        
        return sortDirection[columnIndex] ? 
            aVal.localeCompare(bVal) : 
            bVal.localeCompare(aVal);
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

function viewDetail(index) {
    const data = historyData[index];
    const modal = document.getElementById('detailModal');
    const body = document.getElementById('modalBody');
    
    const statusHtml = data.status === 'success' 
        ? '<span class="status-badge success"><i class="fas fa-check"></i> BERHASIL</span>'
        : '<span class="status-badge error"><i class="fas fa-times"></i> GAGAL</span>';
    
    body.innerHTML = `
        <div class="detail-row">
            <div class="detail-label">Waktu</div>
            <div class="detail-value"><code>${data.time}</code></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Database</div>
            <div class="detail-value"><code>${data.db_name}</code></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Username</div>
            <div class="detail-value"><code>${data.db_user}</code></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Host</div>
            <div class="detail-value"><code>${data.db_host || 'localhost'}</code></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">File SQL</div>
            <div class="detail-value" style="word-break: break-all;"><code>${data.sql_file}</code></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Status</div>
            <div class="detail-value">${statusHtml}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Pesan</div>
            <div class="detail-value" style="white-space: pre-wrap;">${data.message}</div>
        </div>
        ${data.duration ? `
        <div class="detail-row">
            <div class="detail-label">Durasi</div>
            <div class="detail-value"><code>${data.duration} detik</code></div>
        </div>
        ` : ''}
    `;
    
    modal.classList.add('show');
}

function closeModal() {
    document.getElementById('detailModal').classList.remove('show');
}

document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

function exportHistory() {
    const dataStr = JSON.stringify(historyData, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'import-history-' + new Date().toISOString().slice(0,10) + '.json';
    link.click();
    URL.revokeObjectURL(url);
}

function updateStats(visible) {
    document.getElementById('stat-total').textContent = visible;
}

const rowsPerPage = 50;
let currentPage = 1;

function initPagination() {
    const totalRows = rows.length;
    const totalPages = Math.ceil(totalRows / rowsPerPage);
    
    if (totalPages <= 1) return;
    
    const pagination = document.getElementById('pagination');
    let html = '';
    
    html += `<button ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})"><i class="fas fa-chevron-left"></i></button>`;
    
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            html += `<button class="${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
        } else if (i === currentPage - 2 || i === currentPage + 2) {
            html += `<span style="padding: 0 10px;">...</span>`;
        }
    }
    
    html += `<button ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})"><i class="fas fa-chevron-right"></i></button>`;
    
    html += `<span class="pagination-info">Menampilkan ${Math.min((currentPage-1)*rowsPerPage + 1, totalRows)}-${Math.min(currentPage*rowsPerPage, totalRows)} dari ${totalRows}</span>`;
    
    pagination.innerHTML = html;
}

function changePage(page) {
    currentPage = page;
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    
    for (let i = 0; i < rows.length; i++) {
        if (i >= start && i < end) {
            rows[i].style.display = '';
        } else {
            rows[i].style.display = 'none';
        }
    }
    
    initPagination();
}

if (rows.length > 0) {
    initPagination();
}
</script>

</body>
</html>

<?php
function timeAgo($time) {
    $time = time() - $time;
    
    $tokens = array (
        31536000 => 'tahun',
        2592000 => 'bulan',
        604800 => 'minggu',
        86400 => 'hari',
        3600 => 'jam',
        60 => 'menit',
        1 => 'detik'
    );
    
    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits . ' ' . $text . ' lalu';
    }
    return 'baru saja';
}
?>
