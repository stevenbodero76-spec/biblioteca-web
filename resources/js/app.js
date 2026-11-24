document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // 1. Auto-cerrar mensajes flash después de 5.5 segundos
    document.querySelectorAll('.js-auto-dismiss').forEach((banner) => {
        setTimeout(() => {
            banner.classList.add('opacity-0');
            setTimeout(() => banner.remove(), 500);
        }, 5500);
    });

    // 2. Delegación global de formularios (Confirmación + Favoritos AJAX + Loading)
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;

        // Favoritos AJAX sin recargar ni perder el scroll
        if (form.classList.contains('js-favorite-form')) {
            e.preventDefault();
            const btn = form.querySelector('.js-favorite-btn');
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (res.ok) {
                    if (btn) {
                        btn.textContent = data.favorito ? '❤️' : '🤍';
                        btn.title = data.favorito ? 'Quitar de Mi Estantería' : 'Guardar en Mi Estantería';
                    }
                    mostrarToast(data.message || 'Estantería actualizada.');
                }
            } catch {
                form.submit();
            }
            return;
        }

        if ((form.method || 'GET').toUpperCase() === 'GET') return;

        if (form.dataset.confirm && !form.dataset.confirmed) {
            e.preventDefault();
            abrirModalConfirmacion(form);
            return;
        }

        const btn = form.querySelector('button[type="submit"]');
        if (btn && !btn.dataset.noLoading) {
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-wait');
            btn.innerHTML = '⏳ Procesando...';
        }
    });

    // 3. Delegación global para Quick View Modal y Paginación AJAX
    document.addEventListener('click', (e) => {
        const qvBtn = e.target.closest('.js-quickview-btn');
        if (qvBtn) {
            try {
                const book = JSON.parse(qvBtn.dataset.book || '{}');
                abrirQuickView(book);
            } catch {}
            return;
        }

        const pageLink = e.target.closest('.ajax-pagination a');
        if (pageLink && document.getElementById('books-grid-container')) {
            e.preventDefault();
            cargarCatalogoAjax(pageLink.href, true);
        }
    });

    // 4. Pestañas dinámicas de Vitrina Destacada
    document.querySelectorAll('.js-showcase-tab').forEach((tabBtn) => {
        tabBtn.addEventListener('click', () => {
            const target = tabBtn.dataset.tab;
            document.querySelectorAll('.js-showcase-tab').forEach((b) => {
                b.className = 'js-showcase-tab px-4 py-2 rounded-xl text-xs font-bold transition text-slate-300 hover:text-white';
            });
            tabBtn.className = 'js-showcase-tab px-4 py-2 rounded-xl text-xs font-black transition bg-amber-400 text-slate-950';

            document.querySelectorAll('.js-showcase-panel').forEach((panel) => {
                panel.classList.toggle('hidden', panel.id !== `showcase-panel-${target}`);
            });
        });
    });

    // 5. Expandir / colapsar las 12 categorías y filtros móviles
    const toggleCatsBtn = document.getElementById('toggle-extra-cats');
    const extraCatsDiv = document.getElementById('extra-categories');
    if (toggleCatsBtn && extraCatsDiv) {
        toggleCatsBtn.addEventListener('click', () => {
            const isHidden = extraCatsDiv.classList.contains('hidden');
            extraCatsDiv.classList.toggle('hidden', !isHidden);
            extraCatsDiv.classList.toggle('flex', isHidden);
            toggleCatsBtn.textContent = isHidden ? '− Ocultar categorías' : `+ Ver todas (${extraCatsDiv.children.length + 4})`;
        });
    }

    const toggleMobileFilters = document.getElementById('toggle-mobile-filters');
    const filterForm = document.getElementById('live-filter-form');
    if (toggleMobileFilters && filterForm) {
        toggleMobileFilters.addEventListener('click', () => {
            filterForm.classList.toggle('hidden');
            filterForm.classList.toggle('block');
        });
    }

    // 5b. Saneamiento en vivo de campos por tipo (solo letras vs solo dígitos)
    document.addEventListener('input', (e) => {
        const target = e.target;
        if (!(target instanceof HTMLInputElement)) return;

        if (target.dataset.inputMode === 'digits') {
            const cleaned = target.value.replace(/[^0-9]/g, '');
            if (cleaned !== target.value) target.value = cleaned;
        } else if (target.dataset.inputMode === 'letters') {
            const cleaned = target.value.replace(/[^\p{L}\s.'-]/gu, '');
            if (cleaned !== target.value) target.value = cleaned;
        }
    });

    // 6. Filtros y búsqueda en vivo en el Catálogo
    const heroSearchInput = document.getElementById('hero-search-input');
    const searchInput = document.getElementById('live-search-input');
    const catInput = document.getElementById('live-category-input');
    const ordenSelect = document.getElementById('live-orden-select');
    const formatoSelect = document.getElementById('live-formato-select');
    const idiomaSelect = document.getElementById('live-idioma-select');
    const licenciaSelect = document.getElementById('live-licencia-select');
    const stockSelect = document.getElementById('live-stock-select');
    const resetBtn = document.getElementById('live-reset-btn');
    const toggleAdvBtn = document.getElementById('toggle-advanced-filters');
    const advPanel = document.getElementById('advanced-filters-panel');

    if (toggleAdvBtn && advPanel) {
        toggleAdvBtn.addEventListener('click', () => {
            const hidden = advPanel.classList.contains('hidden');
            advPanel.classList.toggle('hidden', !hidden);
            advPanel.classList.toggle('grid', hidden);
        });
    }

    if (filterForm && document.getElementById('books-grid-container')) {
        let debounceTimer = null;

        const ejecutarFiltroVivo = () => {
            const params = new URLSearchParams(new FormData(filterForm));
            for (const [k, v] of Array.from(params.entries())) {
                if (!v) params.delete(k);
            }
            const qs = params.toString();
            const targetUrl = filterForm.action + (qs ? `?${qs}` : '');
            cargarCatalogoAjax(targetUrl, false);
        };

        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            ejecutarFiltroVivo();
        });

        heroSearchInput?.addEventListener('input', () => {
            if (searchInput) searchInput.value = heroSearchInput.value;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(ejecutarFiltroVivo, 280);
        });

        searchInput?.addEventListener('input', () => {
            if (heroSearchInput && heroSearchInput.value !== searchInput.value) {
                heroSearchInput.value = searchInput.value;
            }
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(ejecutarFiltroVivo, 280);
        });

        [ordenSelect, formatoSelect, idiomaSelect, licenciaSelect, stockSelect].forEach((sel) => {
            sel?.addEventListener('change', ejecutarFiltroVivo);
        });

        document.querySelectorAll('.js-cat-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                const catId = chip.dataset.quickCat || '';
                if (catInput) catInput.value = catId;

                document.querySelectorAll('.js-cat-chip').forEach((c) => {
                    if (c.closest('#catalogo-resultados')) {
                        const active = (c.dataset.quickCat || '') === catId;
                        c.className = active
                            ? 'js-cat-chip px-3.5 py-2 rounded-xl text-xs border transition inline-flex items-center gap-1.5 bg-amber-400 text-slate-950 border-amber-400 shadow font-extrabold'
                            : 'js-cat-chip px-3.5 py-2 rounded-xl text-xs border transition inline-flex items-center gap-1.5 bg-slate-900 text-slate-200 border-slate-800 hover:border-amber-400/50 hover:text-white font-bold';
                    }
                });

                ejecutarFiltroVivo();
                document.getElementById('catalogo-resultados')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        resetBtn?.addEventListener('click', () => {
            if (heroSearchInput) heroSearchInput.value = '';
            if (searchInput) searchInput.value = '';
            if (catInput) catInput.value = '';
            if (ordenSelect) ordenSelect.value = '';
            if (formatoSelect) formatoSelect.value = '';
            if (idiomaSelect) idiomaSelect.value = '';
            if (licenciaSelect) licenciaSelect.value = '';
            if (stockSelect) stockSelect.value = '';
            document.querySelector('.js-cat-chip[data-quick-cat=""]')?.click();
        });

        window.aplicarFiltroStock = (estado) => {
            if (stockSelect) stockSelect.value = estado;
            if (advPanel) {
                advPanel.classList.remove('hidden');
                advPanel.classList.add('grid');
            }
            ejecutarFiltroVivo();
            document.getElementById('catalogo-resultados')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };
    }

    // Cerrar QuickView Modal
    const qvModal = document.getElementById('nova-quickview-modal');
    document.getElementById('qv-close')?.addEventListener('click', () => {
        qvModal?.classList.add('hidden');
        qvModal?.classList.remove('flex');
    });
    qvModal?.addEventListener('click', (e) => {
        if (e.target === qvModal) {
            qvModal.classList.add('hidden');
            qvModal.classList.remove('flex');
        }
    });
});

async function cargarCatalogoAjax(url, scrollTop) {
    const container = document.getElementById('books-grid-container');
    if (!container) return;

    container.classList.add('opacity-50');
    try {
        const fetchUrl = new URL(url, window.location.origin);
        fetchUrl.searchParams.set('partial', '1');

        const res = await fetch(fetchUrl.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!res.ok) throw new Error('Network');
        const data = await res.json();
        container.innerHTML = data.html;

        const cleanUrl = new URL(url, window.location.origin);
        cleanUrl.searchParams.delete('partial');
        window.history.replaceState({}, '', cleanUrl.toString());

        if (scrollTop) {
            document.getElementById('catalogo-resultados')?.scrollIntoView({ behavior: 'smooth' });
        }
    } catch {
        window.location.href = url;
    } finally {
        container.classList.remove('opacity-50');
    }
}

function abrirQuickView(book) {
    const modal = document.getElementById('nova-quickview-modal');
    if (!modal || !book.id) return;

    const img = document.getElementById('qv-portada');
    const fallback = document.getElementById('qv-portada-fallback');

    if (book.portada_url && (/^https?:\/\//i.test(book.portada_url) || book.portada_url.startsWith('/storage/'))) {
        img.src = book.portada_url;
        img.alt = book.titulo || '';
        img.classList.remove('hidden');
        fallback.classList.add('hidden');
    } else {
        img.classList.add('hidden');
        fallback.classList.remove('hidden');
    }

    document.getElementById('qv-categoria').textContent = book.categoria || '';
    document.getElementById('qv-titulo').textContent = book.titulo || '';
    document.getElementById('qv-autor').textContent = `Autor: ${book.autor || ''} · ${book.editorial || ''} (${book.anio || ''})`;
    document.getElementById('qv-rating').textContent = book.rating_texto || '';
    document.getElementById('qv-formato').textContent = book.formato || '';
    document.getElementById('qv-idioma').textContent = book.idioma || '';
    document.getElementById('qv-licencia').textContent = book.licencia || '';
    document.getElementById('qv-sinopsis').textContent = book.sinopsis || '';

    const stockEl = document.getElementById('qv-stock');
    stockEl.textContent = book.stock_texto || '';
    stockEl.className = book.disponible
        ? 'text-[10px] font-black px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
        : 'text-[10px] font-black px-2.5 py-0.5 rounded-full bg-rose-500/20 text-rose-200 border border-rose-500/40';

    const readLink = document.getElementById('qv-read-link');
    readLink.href = book.read_url || book.show_url || '#';
    readLink.removeAttribute('target');
    readLink.textContent = book.requires_auth ? '🔒 Inicia sesión para leer' : '📖 Abrir Sala de Lectura';

    document.getElementById('qv-show-link').href = book.show_url || '#';

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function mostrarToast(mensaje) {
    const container = document.getElementById('nova-toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'pointer-events-auto rounded-2xl bg-slate-900 border border-amber-400/50 text-white px-4 py-3 text-xs font-bold shadow-2xl flex items-center justify-between gap-3 transition-opacity duration-300';

    const span = document.createElement('span');
    span.textContent = mensaje;

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.textContent = '✕';
    closeBtn.className = 'text-slate-400 hover:text-white';
    closeBtn.onclick = () => toast.remove();

    toast.append(span, closeBtn);
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 3200);
}

function abrirModalConfirmacion(form) {
    const modal = document.getElementById('nova-confirm-modal');
    const msgEl = document.getElementById('nova-confirm-message');
    const btnConfirm = document.getElementById('nova-confirm-accept');
    const btnCancel = document.getElementById('nova-confirm-cancel');

    if (!modal || !msgEl || !btnConfirm || !btnCancel) {
        if (window.confirm(form.dataset.confirm)) {
            form.dataset.confirmed = 'true';
            form.submit();
        }
        return;
    }

    msgEl.textContent = form.dataset.confirm;
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    const cleanup = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        btnConfirm.onclick = null;
        btnCancel.onclick = null;
    };

    btnCancel.onclick = cleanup;
    btnConfirm.onclick = () => {
        cleanup();
        form.dataset.confirmed = 'true';
        form.requestSubmit();
    };
}
