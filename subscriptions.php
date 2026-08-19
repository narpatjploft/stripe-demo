<?php
$pageTitle   = 'Subscriptions – Stripe';
$pageHeading = 'Subscriptions';
$pageSubtitle = 'View all customer subscriptions, filter by email, and click a row to see transactions.';
$currentPage = 'subscriptions';
$pageCss     = 'subscriptions.css';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

    <div class="section">
        <div class="table-toolbar">
            <div class="section-title" style="margin-bottom:0">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                All Subscriptions
            </div>
            <div class="toolbar-filters">
                <div class="search-wrap">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="email" id="email-input" placeholder="Filter by customer email…">
                </div>
                <div class="per-page-wrap">
                    Status
                    <select id="status-filter">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="trialing">Trialing</option>
                        <option value="past_due">Past due</option>
                        <option value="canceled">Canceled</option>
                        <option value="incomplete">Incomplete</option>
                    </select>
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
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Renews</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody id="subs-tbody"></tbody>
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

<div class="modal-backdrop" id="transactions-modal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Subscription Transactions</div>
            <button class="modal-close" id="transactions-modal-close">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-sub-info" id="modal-sub-info"></div>
        <div class="table-wrap">
            <table class="txn-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Charge</th>
                    </tr>
                </thead>
                <tbody id="transactions-tbody"></tbody>
            </table>
        </div>
    </div>
</div>

<div id="toast"></div>

<script>
    let perPage      = 10;
    let cursorStack  = [null];
    let stackPointer = 0;
    let hasMore      = false;
    let allRows      = [];
    let emailFilter  = '';
    let statusFilter = '';
    let isFiltered   = false;
    let subMap       = {};

    function showToast(msg, type = 'error') {
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
        if (!ts) return '—';
        return new Date(ts * 1000).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function fmtAmount(amount, currency) {
        if (!amount) return '—';
        return `${(amount / 100).toFixed(2)} ${currency}`;
    }

    function statusClass(status) {
        const known = ['active', 'canceled', 'past_due', 'trialing', 'incomplete'];
        return known.includes(status) ? `status-${status}` : 'status-default';
    }

    function invoiceStatusClass(status) {
        const map = {
            paid: 'status-active',
            open: 'status-incomplete',
            void: 'status-canceled',
            uncollectible: 'status-past_due',
            draft: 'status-default',
        };
        return map[status] || 'status-default';
    }

    function renderRows(rows) {
        const tbody = document.getElementById('subs-tbody');
        subMap = {};
        rows.forEach(s => { subMap[s.id] = s; });

        if (!rows.length) {
            tbody.innerHTML = `
                <tr><td colspan="6">
                    <div class="empty-state">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                        No subscriptions found.
                    </div>
                </td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map(s => `
            <tr class="sub-row" data-id="${s.id}">
                <td>
                    <div class="customer-name-cell">
                        <div class="customer-avatar">${initials(s.customer_name, s.customer_email)}</div>
                        <div>
                            <div class="customer-name">${s.customer_name || '<span style="color:var(--muted)">—</span>'}</div>
                            <div class="sub-id">${s.id}</div>
                        </div>
                    </div>
                </td>
                <td><span class="badge-email">${s.customer_email || '<span style="color:var(--muted)">—</span>'}</span></td>
                <td>
                    <div class="plan-name">${s.plan_name || '—'}</div>
                    <div class="plan-price">${s.amount ? fmtAmount(s.amount, s.currency) + ' / ' + s.interval : ''}</div>
                </td>
                <td><span class="status-badge ${statusClass(s.status)}">${s.status.replace('_', ' ')}</span></td>
                <td><span class="badge-date">${s.status === 'canceled' ? '—' : fmtDate(s.current_period_end)}</span></td>
                <td><span class="badge-date">${fmtDate(s.created)}</span></td>
            </tr>
        `).join('');
    }

    function showSkeleton() {
        const tbody = document.getElementById('subs-tbody');
        tbody.innerHTML = Array.from({ length: perPage }, () => `
            <tr class="skeleton-row">
                <td><div class="skeleton-cell" style="width:160px"></div></td>
                <td><div class="skeleton-cell" style="width:140px"></div></td>
                <td><div class="skeleton-cell" style="width:120px"></div></td>
                <td><div class="skeleton-cell" style="width:70px"></div></td>
                <td><div class="skeleton-cell" style="width:90px"></div></td>
                <td><div class="skeleton-cell" style="width:90px"></div></td>
            </tr>`).join('');
    }

    function updatePaginationUI() {
        const total = allRows.length;
        const filterNote = isFiltered ? ' (filtered)' : '';
        document.getElementById('page-info').textContent =
            total ? `Showing ${total} subscription${total !== 1 ? 's' : ''}${filterNote}` : '';

        document.getElementById('btn-prev').disabled = isFiltered || stackPointer === 0;
        document.getElementById('btn-next').disabled = isFiltered || !hasMore;
    }

    async function loadPage(startingAfter = null) {
        showSkeleton();
        document.getElementById('page-info').textContent = 'Loading…';
        document.getElementById('btn-prev').disabled = true;
        document.getElementById('btn-next').disabled = true;

        try {
            const params = new URLSearchParams({ limit: perPage });
            if (emailFilter)  params.set('email', emailFilter);
            if (statusFilter) params.set('status', statusFilter);
            if (!emailFilter && startingAfter) params.set('starting_after', startingAfter);

            const res  = await fetch(`${BASE}/list-subscriptions.php?${params}`);
            const data = await res.json();
            if (data.error) throw new Error(data.error);

            allRows    = data.subscriptions;
            hasMore    = data.has_more;
            isFiltered = !!data.filtered;

            renderRows(allRows);
            updatePaginationUI();
        } catch (e) {
            document.getElementById('subs-tbody').innerHTML =
                `<tr><td colspan="6"><div class="empty-state" style="color:var(--danger)">Error: ${e.message}</div></td></tr>`;
            document.getElementById('page-info').textContent = '';
            showToast(e.message);
        }
    }

    function resetAndLoad() {
        cursorStack  = [null];
        stackPointer = 0;
        loadPage();
    }

    document.getElementById('btn-next').addEventListener('click', () => {
        if (isFiltered || !allRows.length || !hasMore) return;
        const lastId = allRows[allRows.length - 1].id;
        stackPointer++;
        if (stackPointer >= cursorStack.length) cursorStack.push(lastId);
        loadPage(lastId);
    });

    document.getElementById('btn-prev').addEventListener('click', () => {
        if (isFiltered || stackPointer === 0) return;
        stackPointer--;
        loadPage(cursorStack[stackPointer]);
    });

    document.getElementById('per-page').addEventListener('change', function() {
        perPage = parseInt(this.value);
        resetAndLoad();
    });

    document.getElementById('status-filter').addEventListener('change', function() {
        statusFilter = this.value;
        resetAndLoad();
    });

    let emailDebounce;
    document.getElementById('email-input').addEventListener('input', function() {
        clearTimeout(emailDebounce);
        emailDebounce = setTimeout(() => {
            emailFilter = this.value.trim();
            resetAndLoad();
        }, 300);
    });

    function closeTransactionsModal() {
        document.getElementById('transactions-modal').classList.remove('open');
    }

    function renderTransactionRows(transactions) {
        const tbody = document.getElementById('transactions-tbody');

        if (!transactions.length) {
            tbody.innerHTML = `
                <tr><td colspan="5">
                    <div class="empty-state" style="padding:32px 0">No transactions found for this subscription.</div>
                </td></tr>`;
            return;
        }

        tbody.innerHTML = transactions.map(t => `
            <tr>
                <td><span class="badge-date">${fmtDate(t.created)}</span></td>
                <td>
                    <div>${t.invoice_number || t.id}</div>
                    <div class="txn-id">${t.id}</div>
                </td>
                <td>${fmtAmount(t.amount, t.currency)}</td>
                <td><span class="status-badge ${invoiceStatusClass(t.status)}">${t.status}</span></td>
                <td>
                    ${t.charge_id
                        ? `<div class="txn-id">${t.charge_id}</div><div class="txn-id">${t.charge_status || ''}</div>`
                        : '<span style="color:var(--muted)">—</span>'}
                </td>
            </tr>
        `).join('');
    }

    function showTransactionsSkeleton() {
        document.getElementById('transactions-tbody').innerHTML = Array.from({ length: 4 }, () => `
            <tr class="skeleton-row">
                <td><div class="skeleton-cell" style="width:80px"></div></td>
                <td><div class="skeleton-cell" style="width:140px"></div></td>
                <td><div class="skeleton-cell" style="width:70px"></div></td>
                <td><div class="skeleton-cell" style="width:60px"></div></td>
                <td><div class="skeleton-cell" style="width:100px"></div></td>
            </tr>`).join('');
    }

    async function openTransactionsModal(subscriptionId) {
        const sub = subMap[subscriptionId];
        if (!sub) return;

        document.getElementById('modal-sub-info').innerHTML = `
            <div class="customer-avatar">${initials(sub.customer_name, sub.customer_email)}</div>
            <div>
                <div class="customer-name">${sub.customer_name || sub.customer_email || sub.customer_id}</div>
                <div class="modal-sub-meta">${sub.plan_name || 'Subscription'} · ${sub.id}</div>
                <div class="modal-sub-meta">${sub.customer_email || ''}</div>
            </div>
            <span class="status-badge ${statusClass(sub.status)}" style="margin-left:auto">${sub.status.replace('_', ' ')}</span>
        `;

        showTransactionsSkeleton();
        document.getElementById('transactions-modal').classList.add('open');

        try {
            const res  = await fetch(`${BASE}/list-subscription-transactions.php?subscription_id=${encodeURIComponent(subscriptionId)}`);
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            renderTransactionRows(data.transactions);
        } catch (e) {
            document.getElementById('transactions-tbody').innerHTML =
                `<tr><td colspan="5"><div class="empty-state" style="color:var(--danger)">Error: ${e.message}</div></td></tr>`;
        }
    }

    document.getElementById('subs-tbody').addEventListener('click', (e) => {
        const row = e.target.closest('.sub-row');
        if (!row) return;
        openTransactionsModal(row.dataset.id);
    });

    document.getElementById('transactions-modal-close').addEventListener('click', closeTransactionsModal);
    document.getElementById('transactions-modal').addEventListener('click', (e) => {
        if (e.target.id === 'transactions-modal') closeTransactionsModal();
    });

    loadPage();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
