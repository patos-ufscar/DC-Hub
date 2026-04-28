'use strict';

/**
 * DC Hub – Certificate Module
 * Check eligibility and generate/download certificates.
 */
const Certificate = (() => {

    function init() {
        /* Load eligible certificates when modal opens */
        document.getElementById('certificateModal')?.addEventListener('show.bs.modal', loadCertificates);

        /* Go to profile from certificate warning */
        document.getElementById('certGoToProfile')?.addEventListener('click', () => {
            App.closeModal('certificateModal');
            setTimeout(() => App.openModal('profileModal'), 300);
        });
    }

    async function loadCertificates() {
        const list = document.getElementById('certificateList');
        const warning = document.getElementById('certNameWarning');
        if (!list) return;

        const user = App.cfg.user || {};
        if (warning) {
            warning.classList.toggle('d-none', !!user.nome_completo);
        }

        const res = await App.get('certificate.check');
        if (!res.ok) {
            list.innerHTML = '<p class="text-danger">Erro ao carregar certificados.</p>';
            return;
        }

        const certs = res.certificates || [];
        if (certs.length === 0) {
            list.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-award" style="font-size: 2rem;"></i><p class="mt-2">Nenhum certificado disponível.<br><small>Participe de atividades e tenha sua presença validada.</small></p></div>';
            return;
        }

        list.innerHTML = certs.map(c => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">${App.escapeHtml(c.evento_titulo)}</h6>
                            <div class="small text-muted">
                                <i class="bi bi-collection me-1"></i>${c.total_atividades} atividade(s) com presença
                                <i class="bi bi-clock ms-2 me-1"></i>${formatHours(c.total_minutos)} de carga horária
                            </div>
                            ${c.grupo_nome ? `<div class="small text-muted"><i class="bi bi-people me-1"></i>${App.escapeHtml(c.grupo_nome)}</div>` : ''}
                        </div>
                        <button class="btn btn-dc-primary btnGenerateCert" data-evento-id="${c.evento_id}" ${!user.nome_completo ? 'disabled title="Preencha seu nome completo no perfil"' : ''}>
                            <i class="bi bi-download me-1"></i>Gerar
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        list.querySelectorAll('.btnGenerateCert').forEach(btn => {
            btn.addEventListener('click', () => generateCertificate(btn.dataset.eventoId));
        });
    }

    async function generateCertificate(eventoId) {
        const base = App.cfg.baseUrl || '.';
        // Open in new tab for PDF download
        window.open(`${base}/?action=certificate.generate&evento_id=${eventoId}`, '_blank');
    }

    function formatHours(minutes) {
        if (!minutes) return '0h';
        const h = Math.floor(minutes / 60);
        const m = minutes % 60;
        return h + 'h' + (m ? m + 'min' : '');
    }

    document.addEventListener('DOMContentLoaded', init);

    return { loadCertificates };
})();
