<?php
$pageTitle   = 'Customers – Stripe';
$pageHeading = 'Customers';
$pageSubtitle = 'Create and manage your Stripe customers.';
$currentPage = 'customers';
$pageCss     = 'customers.css';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

    <!-- Add Customer -->
    <div class="section">
        <div class="section-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Add New Customer
        </div>
        <form id="add-customer-form" novalidate>
            <div class="form-grid">
                <div class="form-group">
                    <label for="cust-name">Full Name</label>
                    <input type="text" id="cust-name" placeholder="John Doe" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="cust-email">Email Address</label>
                    <input type="email" id="cust-email" placeholder="john@example.com" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="cust-phone">Phone (optional)</label>
                    <input type="tel" id="cust-phone" placeholder="+1 555 000 0000" autocomplete="off">
                </div>
                <div class="form-group" style="justify-content: flex-end; padding-top: 4px;">
                    <button type="submit" class="btn btn-primary" id="add-btn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        <span id="add-btn-text">Create Customer</span>
                    </button>
                </div>
            </div>
            <div id="form-error" style="color:var(--danger);font-size:0.83rem;margin-top:12px;display:none;"></div>
        </form>
    </div>

    <!-- Customer List -->
    <div class="section">
        <div class="table-toolbar">
            <div class="section-title" style="margin-bottom:0">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                All Customers
            </div>
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <div class="search-wrap">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="search-input" placeholder="Filter by name or email…">
                </div>
                <div class="per-page-wrap">
                    Per page
                    <select id="per-page">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="customers-tbody"></tbody>
            </table>
        </div>

        <div class="pagination">
            <div class="page-info" id="page-info">Loading…</div>
            <div class="page-btns">
                <button class="btn-page" id="btn-prev" disabled>← Prev</button>
                <button class="btn-page" id="btn-next" disabled>Next →</button>
            </div>
        </div>
    </div>
</div>
</main>

<!-- ── Subscribe Modal ── -->
<div class="modal-backdrop" id="subscribe-modal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Subscribe Customer</div>
            <button class="modal-close" onclick="closeModal('subscribe-modal')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-customer-info" id="sub-customer-info"></div>
        <div class="form-group">
            <label>Select Plan</label>
            <select id="sub-plan-select"><option value="">Loading plans…</option></select>
        </div>
        <div class="form-group">
            <label>Payment Method</label>
            <select id="sub-pm-select"><option value="">Loading cards…</option></select>
        </div>
        <div class="modal-error" id="sub-error"></div>
        <button class="btn btn-primary" id="sub-submit-btn" onclick="submitSubscribe()">
            <span id="sub-submit-text">Subscribe</span>
        </button>
    </div>
</div>

<!-- ── Charge Modal ── -->
<div class="modal-backdrop" id="charge-modal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Charge Customer</div>
            <button class="modal-close" onclick="closeModal('charge-modal')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-customer-info" id="charge-customer-info"></div>
        <div class="form-group">
            <label>Amount (USD)</label>
            <div class="modal-input-prefix">
                <span>$</span>
                <input type="number" id="charge-amount" placeholder="10.00" min="0.50" step="0.01">
            </div>
        </div>
        <div class="form-group">
            <label>Description (optional)</label>
            <input type="text" id="charge-description" placeholder="e.g. One-time setup fee">
        </div>
        <div class="form-group">
            <label>Payment Method</label>
            <select id="charge-pm-select"><option value="">Loading cards…</option></select>
        </div>
        <div class="modal-error" id="charge-error"></div>
        <button class="btn btn-primary" id="charge-submit-btn" onclick="submitCharge()">
            <span id="charge-submit-text">Charge</span>
        </button>
    </div>
</div>

<div id="toast"></div>

<script>
    let perPage       = 10;
    let cursorStack   = [null];
    let stackPointer  = 0;
    let hasMore       = false;
    let allRows       = [];
    let searchTerm    = '';
    let custMap       = {};

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = 'show ' + type;
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.className = ''; }, 3500);
    }

    function initials(name, email) {
        if (name) return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
        if (email) return email[0].toUpperCase();
        return '?';
    }

    function fmtDate(ts) {
        return new Date(ts * 1000).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function renderRows(rows, newId = null) {
        const tbody = document.getElementById('customers-tbody');

        custMap = {};
        rows.forEach(c => { custMap[c.id] = c; });

        const filtered = rows.filter(c => {
            if (!searchTerm) return true;
            const s = searchTerm.toLowerCase();
            return (c.name.toLowerCase().includes(s) || c.email.toLowerCase().includes(s));
        });

        if (!filtered.length) {
            tbody.innerHTML = `
                <tr><td colspan="5">
                    <div class="empty-state">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        No customers found.
                    </div>
                </td></tr>`;
            return;
        }

        tbody.innerHTML = filtered.map(c => `
            <tr id="row-${c.id}" class="${c.id === newId ? 'new-row' : ''}">
                <td>
                    <div class="customer-name-cell">
                        <div class="customer-avatar">${initials(c.name, c.email)}</div>
                        <div>
                            <div class="customer-name">${c.name || '<span style="color:var(--muted)">—</span>'}</div>
                            <div class="customer-id">${c.id}</div>
                        </div>
                    </div>
                </td>
                <td><span class="badge-email">${c.email || '<span style="color:var(--muted)">—</span>'}</span></td>
                <td><span class="badge-phone">${c.phone || '—'}</span></td>
                <td><span class="badge-date">${fmtDate(c.created)}</span></td>
                <td>
                    <div class="action-btns">
                        <a href="${BASE}/add-card.php?customer_id=${c.id}" class="btn-action btn-action-cards">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            Cards
                        </a>
                        <button class="btn-action btn-action-sub" onclick="openSubscribeModal('${c.id}')">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                            Subscribe
                        </button>
                        <button class="btn-action btn-action-charge" onclick="openChargeModal('${c.id}')">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            Charge
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function showSkeleton() {
        const tbody = document.getElementById('customers-tbody');
        tbody.innerHTML = Array.from({ length: perPage }, () => `
            <tr class="skeleton-row">
                <td><div class="skeleton-cell" style="width:160px"></div></td>
                <td><div class="skeleton-cell" style="width:140px"></div></td>
                <td><div class="skeleton-cell" style="width:100px"></div></td>
                <td><div class="skeleton-cell" style="width:90px"></div></td>
                <td><div class="skeleton-cell" style="width:60px"></div></td>
            </tr>`).join('');
    }

    async function loadPage(startingAfter = null, endingBefore = null, newId = null) {
        showSkeleton();
        document.getElementById('page-info').textContent = 'Loading…';
        document.getElementById('btn-prev').disabled = true;
        document.getElementById('btn-next').disabled = true;

        try {
            const params = new URLSearchParams({ limit: perPage });
            if (startingAfter) params.set('starting_after', startingAfter);
            if (endingBefore)  params.set('ending_before',  endingBefore);

            const res  = await fetch(`${BASE}/list-customers.php?${params}`);
            const data = await res.json();
            if (data.error) throw new Error(data.error);

            allRows = data.customers;
            hasMore = data.has_more;

            renderRows(allRows, newId);

            const total = allRows.length;
            document.getElementById('page-info').textContent =
                total ? `Showing ${total} customer${total !== 1 ? 's' : ''}` : '';

            document.getElementById('btn-prev').disabled = (stackPointer === 0);
            document.getElementById('btn-next').disabled = !hasMore;
        } catch (e) {
            document.getElementById('customers-tbody').innerHTML =
                `<tr><td colspan="5"><div class="empty-state" style="color:var(--danger)">Error: ${e.message}</div></td></tr>`;
            document.getElementById('page-info').textContent = '';
        }
    }

    document.getElementById('btn-next').addEventListener('click', () => {
        if (!allRows.length || !hasMore) return;
        const lastId = allRows[allRows.length - 1].id;
        stackPointer++;
        if (stackPointer >= cursorStack.length) cursorStack.push(lastId);
        loadPage(lastId);
    });

    document.getElementById('btn-prev').addEventListener('click', () => {
        if (stackPointer === 0) return;
        stackPointer--;
        const cursor = cursorStack[stackPointer];
        loadPage(cursor);
    });

    document.getElementById('per-page').addEventListener('change', function() {
        perPage      = parseInt(this.value);
        cursorStack  = [null];
        stackPointer = 0;
        loadPage();
    });

    let searchDebounce;
    document.getElementById('search-input').addEventListener('input', function() {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            searchTerm = this.value.trim();
            renderRows(allRows);
        }, 200);
    });

    document.getElementById('add-customer-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const name  = document.getElementById('cust-name').value.trim();
        const email = document.getElementById('cust-email').value.trim();
        const phone = document.getElementById('cust-phone').value.trim();
        const errEl = document.getElementById('form-error');

        errEl.style.display = 'none';
        errEl.textContent   = '';

        if (!name && !email) {
            errEl.textContent   = 'Please provide at least a name or email.';
            errEl.style.display = 'block';
            return;
        }

        const btn     = document.getElementById('add-btn');
        const btnText = document.getElementById('add-btn-text');
        btn.disabled  = true;
        btnText.innerHTML = '<span class="spinner"></span> Creating…';

        try {
            const res  = await fetch(`${BASE}/create-customer.php`, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ name, email, phone }),
            });
            const data = await res.json();
            if (data.error) throw new Error(data.error);

            showToast(`Customer "${data.customer.name || data.customer.email}" created ✓`);

            document.getElementById('cust-name').value  = '';
            document.getElementById('cust-email').value = '';
            document.getElementById('cust-phone').value = '';

            cursorStack  = [null];
            stackPointer = 0;
            await loadPage(null, null, data.customer.id);

        } catch (err) {
            errEl.textContent   = err.message;
            errEl.style.display = 'block';
        } finally {
            btn.disabled        = false;
            btnText.textContent = 'Create Customer';
        }
    });

    loadPage();

    let activeCust = null;

    function customerInfoHTML(c) {
        return `
            <div class="modal-avatar">${initials(c.name, c.email)}</div>
            <div>
                <div class="modal-cname">${c.name || c.id}</div>
                ${c.email ? `<div class="modal-cemail">${c.email}</div>` : ''}
            </div>`;
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
    }

    document.querySelectorAll('.modal-backdrop').forEach(bd => {
        bd.addEventListener('click', e => { if (e.target === bd) bd.classList.remove('open'); });
    });

    let cachedPlans = null;
    async function loadPlansIntoSelect(selectId) {
        const sel = document.getElementById(selectId);
        sel.innerHTML = '<option value="">Loading…</option>';
        try {
            if (!cachedPlans) {
                const res  = await fetch(`${BASE}/list-plans.php`);
                const data = await res.json();
                if (data.error) throw new Error(data.error);
                cachedPlans = data.plans;
            }
            if (!cachedPlans.length) {
                sel.innerHTML = '<option value="">No plans available</option>';
                return;
            }
            sel.innerHTML = cachedPlans.map(p =>
                `<option value="${p.id}">${p.name} – ${(p.amount/100).toFixed(2)} ${p.currency} / ${p.interval}</option>`
            ).join('');
        } catch (e) {
            sel.innerHTML = `<option value="">Error: ${e.message}</option>`;
        }
    }

    async function loadCardsIntoSelect(selectId, customerId) {
        const sel = document.getElementById(selectId);
        sel.innerHTML = '<option value="">Loading…</option>';
        try {
            const res  = await fetch(`${BASE}/list-cards.php?customer_id=${encodeURIComponent(customerId)}`);
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            if (!data.cards.length) {
                sel.innerHTML = '<option value="">No saved cards – add one first</option>';
                return;
            }
            sel.innerHTML = data.cards.map(card =>
                `<option value="${card.id}">${card.brand} •••• ${card.last4} (exp ${card.exp_month}/${card.exp_year})${card.is_default ? ' ★' : ''}</option>`
            ).join('');
            const def = data.cards.find(c => c.is_default);
            if (def) sel.value = def.id;
        } catch (e) {
            sel.innerHTML = `<option value="">Error: ${e.message}</option>`;
        }
    }

    async function openSubscribeModal(customerId) {
        activeCust = custMap[customerId];
        if (!activeCust) return;
        document.getElementById('sub-customer-info').innerHTML = customerInfoHTML(activeCust);
        document.getElementById('sub-error').style.display = 'none';
        document.getElementById('subscribe-modal').classList.add('open');
        await Promise.all([
            loadPlansIntoSelect('sub-plan-select'),
            loadCardsIntoSelect('sub-pm-select', activeCust.id),
        ]);
    }

    async function submitSubscribe() {
        const priceId = document.getElementById('sub-plan-select').value;
        const pmId    = document.getElementById('sub-pm-select').value;
        const errEl   = document.getElementById('sub-error');
        errEl.style.display = 'none';

        if (!priceId) { errEl.textContent = 'Please select a plan.';           errEl.style.display = 'block'; return; }
        if (!pmId)    { errEl.textContent = 'Please select a payment method.'; errEl.style.display = 'block'; return; }

        const btn  = document.getElementById('sub-submit-btn');
        const text = document.getElementById('sub-submit-text');
        btn.disabled = true;
        text.innerHTML = '<span class="spinner"></span> Subscribing…';

        try {
            const res  = await fetch(`${BASE}/create-subscription.php`, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({
                    customer_id: activeCust.id,
                    price_id: priceId,
                    payment_method_id: pmId,
                }),
            });
            const data = await res.json();
            if (data.error) throw new Error(data.error);

            closeModal('subscribe-modal');
            showToast(`Subscribed "${activeCust.name || activeCust.id}" successfully ✓`);
        } catch (err) {
            errEl.textContent   = err.message;
            errEl.style.display = 'block';
        } finally {
            btn.disabled   = false;
            text.textContent = 'Subscribe';
        }
    }

    async function openChargeModal(customerId) {
        activeCust = custMap[customerId];
        if (!activeCust) return;
        document.getElementById('charge-customer-info').innerHTML = customerInfoHTML(activeCust);
        document.getElementById('charge-amount').value      = '';
        document.getElementById('charge-description').value = '';
        document.getElementById('charge-error').style.display = 'none';
        document.getElementById('charge-modal').classList.add('open');
        await loadCardsIntoSelect('charge-pm-select', activeCust.id);
    }

    async function submitCharge() {
        const amountRaw = parseFloat(document.getElementById('charge-amount').value);
        const desc      = document.getElementById('charge-description').value.trim();
        const pmId      = document.getElementById('charge-pm-select').value;
        const errEl     = document.getElementById('charge-error');
        errEl.style.display = 'none';

        if (!amountRaw || amountRaw < 0.50) { errEl.textContent = 'Minimum charge is $0.50.';    errEl.style.display = 'block'; return; }
        if (!pmId)                           { errEl.textContent = 'Please select a payment method.'; errEl.style.display = 'block'; return; }

        const amount = Math.round(amountRaw * 100);

        const btn  = document.getElementById('charge-submit-btn');
        const text = document.getElementById('charge-submit-text');
        btn.disabled = true;
        text.innerHTML = '<span class="spinner"></span> Charging…';

        try {
            const res  = await fetch(`${BASE}/create-charge.php`, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({
                    customer_id: activeCust.id,
                    payment_method_id: pmId,
                    amount,
                    currency: 'usd',
                    description: desc,
                }),
            });
            const data = await res.json();
            if (data.error) throw new Error(data.error);

            closeModal('charge-modal');
            showToast(`$${(data.payment.amount / 100).toFixed(2)} charged successfully ✓`);
        } catch (err) {
            errEl.textContent   = err.message;
            errEl.style.display = 'block';
        } finally {
            btn.disabled   = false;
            text.textContent = 'Charge';
        }
    }
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
