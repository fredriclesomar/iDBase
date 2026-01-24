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