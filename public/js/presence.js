'use strict';

/**
 * DC Hub – Presence Module
 * User QR code display and organizer check-in (scan + manual list).
 */
const Presence = (() => {

    let qrScanner = null;
    let checkinUsers = [];
    let scanCooldown = false;

    function init() {
        document.getElementById('presenceQrModal')?.addEventListener('show.bs.modal', loadUserQr);
        document.getElementById('checkinPanelModal')?.addEventListener('shown.bs.modal', startScanner);
        document.getElementById('checkinPanelModal')?.addEventListener('hidden.bs.modal', stopScanner);

        document.getElementById('checkinSearchInput')?.addEventListener('input', (e) => {
            renderCheckinList(e.target.value.trim().toLowerCase());
        });

        document.getElementById('btnConfirmPresences')?.addEventListener('click', confirmSelectedPresences);
    }

    function getQrLib() {
        const lib = window.QRCode;
        if (!lib) return null;
        if (typeof lib.toDataURL === 'function') return lib;
        if (lib.default && typeof lib.default.toDataURL === 'function') return lib.default;
        return null;
    }

    function qrToDataUrl(QR, text, opts) {
        return new Promise((resolve, reject) => {
            try {
                const result = QR.toDataURL(text, opts, (err, url) => {
                    if (err) reject(err);
                    else resolve(url);
                });
                if (result && typeof result.then === 'function') {
                    result.then(resolve).catch(reject);
                } else if (typeof result === 'string') {
                    resolve(result);
                }
            } catch (e) {
                reject(e);
            }
        });
    }

    async function renderQrImage(container, text) {
        if (!text) {
            container.innerHTML = '<p class="text-danger small mb-0">UUID inválido.</p>';
            return;
        }

        const QR = getQrLib();
        if (!QR) {
            container.innerHTML = `
                <p class="text-warning small mb-2">Biblioteca QR não carregou. Recarregue a página (Ctrl+F5).</p>
                <p class="font-monospace small mb-0 text-break">${App.escapeHtml(text)}</p>`;
            return;
        }

        const opts = {
            width: 220,
            margin: 2,
            errorCorrectionLevel: 'M',
            color: { dark: '#1a1a2e', light: '#ffffff' }
        };

        try {
            const dataUrl = await qrToDataUrl(QR, text, opts);
            const img = document.createElement('img');
            img.src = dataUrl;
            img.alt = 'QR Code de presença';
            img.width = 220;
            img.height = 220;
            img.className = 'rounded border';
            container.appendChild(img);
        } catch (err) {
            console.error('QR render:', err);
            try {
                const canvas = document.createElement('canvas');
                container.appendChild(canvas);
                await new Promise((resolve, reject) => {
                    QR.toCanvas(canvas, text, opts, (e) => (e ? reject(e) : resolve()));
                });
            } catch {
                container.innerHTML = `
                    <p class="text-muted small mb-2">Não foi possível gerar a imagem. Use o código abaixo no check-in:</p>
                    <p class="font-monospace small mb-0 text-break user-select-all">${App.escapeHtml(text)}</p>`;
            }
        }
    }

    async function loadUserQr() {
        const canvasWrap = document.getElementById('presenceQrCanvas');
        const uuidEl = document.getElementById('presenceQrUuid');
        if (!canvasWrap) return;

        if (!App.isLoggedIn()) {
            canvasWrap.innerHTML = '<p class="text-muted small">Faça login para ver seu QR.</p>';
            return;
        }

        canvasWrap.innerHTML = '<div class="spinner-border text-secondary" role="status"></div>';

        const res = await App.get('registration.myQr');
        if (!res.ok) {
            canvasWrap.innerHTML = `<p class="text-danger small">${App.escapeHtml(res.error || 'Erro ao carregar QR.')}</p>`;
            return;
        }

        const uuid = res.presenca_uuid;
        if (App.cfg.user) {
            App.cfg.user.presenca_uuid = uuid;
        }
        if (uuidEl) uuidEl.textContent = uuid;

        canvasWrap.innerHTML = '';
        await renderQrImage(canvasWrap, uuid);
    }

    async function openCheckinPanel(activityId, title) {
        document.getElementById('checkinActivityId').value = activityId;
        App.setText('checkinActivityTitle', title);
        document.getElementById('generatedCode')?.classList.add('d-none');
        document.getElementById('checkinScanResult')?.classList.add('d-none');
        const search = document.getElementById('checkinSearchInput');
        if (search) search.value = '';

        const res = await App.get(`registration.checkinList&atividade_id=${activityId}`);
        const list = document.getElementById('attendeesList');
        if (!res.ok || !list) {
            if (list) list.innerHTML = '<p class="text-danger small">Erro ao carregar lista.</p>';
        } else {
            checkinUsers = res.users || [];
            renderCheckinList('');
        }

        App.openModal('checkinPanelModal');
    }

    function methodLabel(method) {
        const labels = { qr: 'QR', manual: 'Manual', codigo: 'Código' };
        return labels[method] || method || '';
    }

    function renderCheckinList(filter) {
        const list = document.getElementById('attendeesList');
        if (!list) return;

        const filtered = checkinUsers.filter(u => {
            if (!filter) return true;
            const name = (u.nome_exibicao || '').toLowerCase();
            const email = (u.email || '').toLowerCase();
            return name.includes(filter) || email.includes(filter);
        });

        if (filtered.length === 0) {
            list.innerHTML = '<p class="text-muted small mb-0">Nenhum inscrito ainda. Use o scanner QR quando alguém chegar.</p>';
            return;
        }

        list.innerHTML = filtered.map(u => {
            const present = u.status === 'presente';
            const rsvp = u.status === 'rsvp';
            const log = present && u.metodo_validacao
                ? `<span class="badge bg-light text-dark ms-1">${methodLabel(u.metodo_validacao)}</span>`
                : '';
            const statusBadge = present
                ? `<span class="badge bg-success">Presente</span>${log}`
                : '<span class="badge bg-warning text-dark">Inscrito</span>';

            return `
            <div class="checkin-user-row d-flex align-items-center gap-2 py-2 border-bottom" data-user-id="${u.user_id}">
                <input type="checkbox" class="form-check-input flex-shrink-0 checkin-user-cb"
                       value="${u.user_id}" id="chk_${u.user_id}" ${present ? 'disabled' : ''}>
                <label class="form-check-label flex-grow-1 mb-0" for="chk_${u.user_id}">
                    <strong>${App.escapeHtml(u.nome_exibicao || u.email)}</strong>
                    <div class="small text-muted">${App.escapeHtml(u.email)}</div>
                </label>
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    ${statusBadge}
                    ${present ? '' : `<button type="button" class="btn btn-sm btn-outline-success btnMarkOne" data-id="${u.user_id}"><i class="bi bi-check-lg"></i></button>`}
                </div>
            </div>`;
        }).join('');

        list.querySelectorAll('.btnMarkOne').forEach(btn => {
            btn.addEventListener('click', () => markUsers([btn.dataset.id]));
        });
    }

    async function markUsers(userIds) {
        const actId = document.getElementById('checkinActivityId')?.value;
        if (!actId || userIds.length === 0) return;

        const res = await App.api('registration.validate', {
            body: { atividade_id: actId, user_ids: userIds }
        });

        if (res.ok) {
            App.toast(res.message || 'Presença confirmada!', 'success');
            await refreshCheckinList();
        } else {
            App.toast(res.error || 'Erro', 'danger');
        }
    }

    async function confirmSelectedPresences() {
        const list = document.getElementById('attendeesList');
        const checked = list?.querySelectorAll('.checkin-user-cb:checked:not(:disabled)') || [];
        const userIds = Array.from(checked).map(cb => cb.value);
        if (userIds.length === 0) {
            App.toast('Selecione ao menos um participante.', 'warning');
            return;
        }
        await markUsers(userIds);
    }

    async function refreshCheckinList() {
        const actId = document.getElementById('checkinActivityId')?.value;
        if (!actId) return;
        const filter = document.getElementById('checkinSearchInput')?.value.trim().toLowerCase() || '';
        const res = await App.get(`registration.checkinList&atividade_id=${actId}`);
        if (res.ok) {
            checkinUsers = res.users || [];
            renderCheckinList(filter);
        }
    }

    async function handleScan(decodedText) {
        if (scanCooldown) return;
        const actId = document.getElementById('checkinActivityId')?.value;
        if (!actId) return;

        scanCooldown = true;
        const resultEl = document.getElementById('checkinScanResult');
        const res = await App.api('registration.scanPresence', {
            body: { atividade_id: actId, presenca_uuid: decodedText.trim() }
        });

        if (resultEl) {
            resultEl.classList.remove('d-none');
            resultEl.className = `mt-2 alert ${res.ok ? 'alert-success' : 'alert-danger'}`;
            resultEl.textContent = res.message || res.error || (res.ok ? 'Presença registrada.' : 'Erro.');
        }

        if (res.ok) {
            await refreshCheckinList();
            if (window.ActivitiesManage && typeof ActivitiesManage.refreshIfOpen === 'function') {
                ActivitiesManage.refreshIfOpen();
            }
        }

        setTimeout(() => { scanCooldown = false; }, 2000);
    }

    function startScanner() {
        if (typeof Html5Qrcode === 'undefined') return;
        const containerId = 'checkinQrReader';
        const container = document.getElementById(containerId);
        if (!container) return;

        stopScanner();
        container.innerHTML = '';

        qrScanner = new Html5Qrcode(containerId);
        qrScanner.start(
            { facingMode: 'environment' },
            { fps: 8, qrbox: { width: 220, height: 220 } },
            handleScan,
            () => {}
        ).catch(() => {
            container.innerHTML = '<p class="small text-muted p-2 mb-0">Câmera indisponível. Use a lista manual abaixo.</p>';
        });
    }

    function stopScanner() {
        if (!qrScanner) return;
        const scanner = qrScanner;
        qrScanner = null;
        scanner.stop().catch(() => {}).finally(() => {
            scanner.clear().catch(() => {});
        });
    }

    document.addEventListener('DOMContentLoaded', init);

    return { openCheckinPanel, loadUserQr };
})();

window.Presence = Presence;
