/* Draw on the rendered page; submit only the transparent ink and its page coordinates. */
function initOperationsDocumentSigning(workerUrl) {
    'use strict';
    const lib = window['pdfjs-dist/build/pdf'];
    const el = id => document.getElementById('oa-sign-' + id);
    const paper = el('pdf'), ink = el('ink'), ctx = ink.getContext('2d');
    let pdf = null, task = null, page = 1, generation = 0, ready = false;
    let pointer = null, bounds = null, submitting = false;
    function clear() {
        ctx.clearRect(0, 0, ink.width, ink.height);
        bounds = null; pointer = null;
        el('data').value = '';
        el('save').disabled = true;
    }
    function controls() {
        el('prev').disabled = !ready || submitting || page <= 1;
        el('next').disabled = !ready || submitting || !pdf || page >= pdf.numPages;
        el('attachment').disabled = submitting;
        el('clear').disabled = submitting;
        el('save').disabled = !ready || submitting || !el('data').value;
    }
    async function render() {
        const token = ++generation;
        ready = false; clear(); controls();
        el('preview').hidden = true;
        el('status').textContent = 'Loading page…';
        try {
            const documentPage = await pdf.getPage(page);
            if (token !== generation) return;
            const natural = documentPage.getViewport({scale: 1});
            const viewport = documentPage.getViewport({scale: Math.min(2, 1400 / Math.max(natural.width, natural.height))});
            // Each render gets a separate canvas so an obsolete render cannot overwrite a new page.
            const buffer = document.createElement('canvas');
            buffer.width = Math.ceil(viewport.width); buffer.height = Math.ceil(viewport.height);
            await documentPage.render({canvasContext: buffer.getContext('2d'), viewport: viewport}).promise;
            if (token !== generation) return;
            paper.width = ink.width = buffer.width; paper.height = ink.height = buffer.height;
            paper.getContext('2d').drawImage(buffer, 0, 0);
            el('page').value = page;
            el('page-label').textContent = 'Page ' + page + ' of ' + pdf.numPages;
            el('preview').hidden = false;
            el('status').textContent = 'Draw on the document to sign this page.';
            ready = true; controls();
        } catch (error) {
            if (token !== generation) return;
            el('status').textContent = 'This page could not be displayed. Select the document again to retry.';
        }
    }
    async function load() {
        const token = ++generation;
        ready = false; pdf = null; clear(); controls();
        el('preview').hidden = true; el('page-label').textContent = '';
        el('status').textContent = 'Loading document…';
        if (task) task.destroy();
        try {
            task = lib.getDocument({url: el('attachment').selectedOptions[0].dataset.url, isEvalSupported: false});
            const loaded = await task.promise;
            if (token !== generation) return;
            pdf = loaded; page = 1; await render();
        } catch (error) {
            if (token !== generation) return;
            el('status').textContent = 'Unable to preview this PDF. It may be protected or unsupported. Select another document or reload to retry.';
        }
    }
    function point(event) {
        const rect = ink.getBoundingClientRect();
        return {x: Math.max(0, Math.min(ink.width, (event.clientX - rect.left) * ink.width / rect.width)),
            y: Math.max(0, Math.min(ink.height, (event.clientY - rect.top) * ink.height / rect.height))};
    }
    function include(p) {
        if (!bounds) bounds = {left: p.x, top: p.y, right: p.x, bottom: p.y};
        bounds.left = Math.min(bounds.left, p.x); bounds.right = Math.max(bounds.right, p.x);
        bounds.top = Math.min(bounds.top, p.y); bounds.bottom = Math.max(bounds.bottom, p.y);
    }
    ink.addEventListener('pointerdown', event => {
        if (!ready || submitting || pointer !== null || event.button !== 0) return;
        event.preventDefault(); pointer = event.pointerId; ink.setPointerCapture(pointer);
        const p = point(event); include(p);
        ctx.strokeStyle = '#152c54'; ctx.fillStyle = '#152c54'; ctx.lineWidth = 3;
        ctx.lineCap = 'round'; ctx.lineJoin = 'round';
        ctx.beginPath(); ctx.arc(p.x, p.y, 1.5, 0, Math.PI * 2); ctx.fill();
        ctx.beginPath(); ctx.moveTo(p.x, p.y);
        el('save').disabled = true;
    });
    ink.addEventListener('pointermove', event => {
        if (event.pointerId !== pointer) return;
        const p = point(event); include(p); ctx.lineTo(p.x, p.y); ctx.stroke();
    });
    function finish(event) {
        if (event.pointerId !== pointer) return;
        pointer = null;
        const x = Math.max(0, Math.floor(bounds.left - 3)), y = Math.max(0, Math.floor(bounds.top - 3));
        const w = Math.min(ink.width, Math.ceil(bounds.right + 3)) - x;
        const h = Math.min(ink.height, Math.ceil(bounds.bottom + 3)) - y;
        const crop = document.createElement('canvas'); crop.width = w; crop.height = h;
        crop.getContext('2d').drawImage(ink, x, y, w, h, 0, 0, w, h);
        el('data').value = crop.toDataURL('image/png');
        el('x').value = x / ink.width; el('y').value = y / ink.height;
        el('w').value = w / ink.width; el('h').value = h / ink.height;
        controls();
    }
    ink.addEventListener('pointerup', finish);
    ink.addEventListener('pointercancel', finish);
    ink.addEventListener('lostpointercapture', finish);
    el('clear').addEventListener('click', clear);
    el('attachment').addEventListener('change', load);
    el('prev').addEventListener('click', () => { if (ready && page > 1) { page--; render(); } });
    el('next').addEventListener('click', () => { if (ready && page < pdf.numPages) { page++; render(); } });
    $('#operations-sign-form').appForm({isModal: false,
        onSubmit: function () { submitting = true; /* Keep attachment_id enabled until serialization. */ },
        beforeAjaxSubmit: function () { controls(); },
        onSuccess: oaFormFeedback,
        onError: function () { submitting = false; controls(); return true; },
        onAjaxSuccess: function () { submitting = false; controls(); }
    });
    // appForm does not invoke its onError callback for HTTP/network errors.
    $(document).on('ajaxComplete.oaDocumentSigning', function (event, xhr, settings) {
        if (submitting && new URL(settings.url, window.location.href).pathname === new URL(document.getElementById('operations-sign-form').action).pathname) {
            submitting = false; controls();
        }
    });
    if (!lib) { el('status').textContent = 'PDF preview could not load. Reload this page to retry.'; return; }
    lib.GlobalWorkerOptions.workerSrc = workerUrl;
    load();
}
