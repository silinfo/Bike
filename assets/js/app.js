(() => {
    'use strict';
    const $ = (s, ctx = document) => ctx.querySelector(s);
    const $$ = (s, ctx = document) => [...ctx.querySelectorAll(s)];
    // Igual que money() en PHP: 3.017,00 € (Intl es-ES no agrupa números de 4 cifras)
    const fmt = v => {
        const [int, dec] = Number(v).toFixed(2).split('.');
        return int.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + dec + ' €';
    };

    // --- Menú móvil ---
    const navToggle = $('#navToggle');
    const header = $('#siteHeader');
    if (navToggle) {
        navToggle.addEventListener('click', () => {
            document.documentElement.style.setProperty('--nav-top', header.getBoundingClientRect().bottom + 'px');
            const open = document.body.classList.toggle('nav-open');
            navToggle.setAttribute('aria-expanded', String(open));
        });
        // En móvil el primer toque en "Bicicletas"/"Accesorios" abre el submenú
        $$('.has-mega > a').forEach(a => a.addEventListener('click', e => {
            if (window.matchMedia('(max-width: 900px)').matches) {
                const item = a.parentElement;
                if (!item.classList.contains('open')) {
                    e.preventDefault();
                    item.classList.add('open');
                }
            }
        }));
    }

    // --- Buscador ---
    const searchToggle = $('#searchToggle');
    const searchBar = $('#searchBar');
    searchToggle?.addEventListener('click', () => {
        searchBar.classList.toggle('open');
        if (searchBar.classList.contains('open')) $('input', searchBar).focus();
    });

    // --- Filtros de la tienda ---
    $('#filtersToggle')?.addEventListener('click', () => $('#shopSidebar').classList.toggle('open'));
    $$('[data-range-output]').forEach(r => {
        const out = document.getElementById(r.dataset.rangeOutput);
        r.addEventListener('input', () => { out.textContent = fmt(r.value); });
    });

    // --- Toast ---
    const toast = $('#toast');
    let toastTimer;
    function showToast(html, isError = false) {
        toast.innerHTML = html;
        toast.classList.toggle('error', isError);
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('show'), 3500);
    }

    function updateCartCount(n) {
        const el = $('#cartCount');
        if (!el) return;
        el.textContent = n;
        el.hidden = n === 0;
        el.classList.remove('bump');
        void el.offsetWidth; // reinicia la animación
        el.classList.add('bump');
    }

    // --- Cantidad +/- ---
    $$('.qty').forEach(box => {
        const input = $('input', box);
        $$('button[data-qty]', box).forEach(btn => btn.addEventListener('click', () => {
            input.value = Math.max(1, Math.min(99, (parseInt(input.value, 10) || 1) + parseInt(btn.dataset.qty, 10)));
        }));
    });

    // --- Añadir al carrito por AJAX ---
    const addForm = $('#addToCartForm');
    // getAttribute: el formulario tiene un input name="action" que oculta form.action
    const cartUrl = addForm?.getAttribute('action');
    if (addForm) addForm.noValidate = true; // mostramos nuestros propios avisos
    addForm?.addEventListener('submit', async e => {
        e.preventDefault();
        const picker = $('.variant-picker', addForm);
        if (picker && !$('input[name="variant_id"]:checked', addForm)) {
            picker.classList.add('invalid');
            showToast('Selecciona una talla', true);
            return;
        }
        const btn = $('button[type="submit"]', addForm);
        btn.disabled = true;
        try {
            const res = await fetch(cartUrl, {
                method: 'POST',
                body: new FormData(addForm),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            if (data.ok) {
                updateCartCount(data.count);
                showToast(`${data.message} <a href="${cartUrl}">Ver carrito</a>`);
            } else {
                showToast(data.message, true);
            }
        } catch {
            addForm.submit(); // sin JS/fetch: envío normal
        } finally {
            btn.disabled = false;
        }
    });
    $$('.variant input').forEach(i => i.addEventListener('change', () => i.closest('.variant-picker').classList.remove('invalid')));

    // --- Carrito: actualiza al cambiar cantidad ---
    $$('[data-autosubmit]').forEach(i => i.addEventListener('change', () => i.form.submit()));

    // --- Checkout: recogida en tienda oculta la dirección y el envío ---
    const checkout = $('#checkoutForm');
    if (checkout) {
        const fields = $('#shippingFields');
        const shipEl = $('#shippingCost');
        const totalEl = $('#orderTotal');
        const refresh = () => {
            const pickup = $('input[name="payment_method"]:checked', checkout)?.value === 'store';
            fields.classList.toggle('hidden', pickup);
            const ship = pickup ? 0 : parseFloat(shipEl.dataset.cost);
            shipEl.textContent = ship > 0 ? fmt(ship) : 'Gratis';
            totalEl.textContent = fmt(parseFloat(totalEl.dataset.subtotal) + ship);
            const btn = $('#submitOrder');
            const card = $('input[name="payment_method"]:checked', checkout)?.value === 'card';
            btn.textContent = card ? btn.dataset.labelCard : btn.dataset.label;
        };
        // Evita pedidos duplicados por doble clic
        checkout.addEventListener('submit', () => {
            const btn = $('#submitOrder');
            setTimeout(() => { btn.disabled = true; btn.textContent = 'Procesando…'; }, 0);
        });
        $$('input[name="payment_method"]', checkout).forEach(r => r.addEventListener('change', refresh));
        refresh();
    }
})();
