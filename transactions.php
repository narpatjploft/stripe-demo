<?php
$pageTitle      = 'Transactions – Stripe';
$pageHeading    = 'Transactions';
$pageSubtitle   = 'View all customer payments and filter by email or status.';
$currentPage    = 'transactions';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

    <div class="section">
        <div class="table-toolbar">
            <div class="section-title" style="margin-bottom:0">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                All Transactions
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
                        <option value="succeeded">Succeeded</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
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
                        <th>Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="txn-tbody"></tbody>
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
        const known = ['succeeded', 'pending', 'failed'];
        return known.includes(status) ? `status-${status}` : 'status-default';
    }

    function esc(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderRows(rows) {
        const tbody = document.getElementById('txn-tbody');

        if (!rows.length) {
            tbody.innerHTML = `
                <tr><td colspan="7">
                    <div class="empty-state">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        No transactions found.
                    </div>
                </td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map(t => `
            <tr>
                <td>
                    <div class="customer-name-cell">
                        <div class="customer-avatar">${initials(t.customer_name, t.customer_email)}</div>
                        <div>
                            <div class="customer-name">${esc(t.customer_name) || '<span style="color:var(--muted)">—</span>'}</div>
                            <div class="txn-id">${esc(t.id)}</div>
                        </div>
                    </div>
                </td>
                <td><span class="badge-email">${esc(t.customer_email) || '<span style="color:var(--muted)">—</span>'}</span></td>
                <td><span class="badge-type">${esc(t.type)}</span></td>
                <td><span class="desc-text" title="${esc(t.description)}">${esc(t.description) || '—'}</span></td>
                <td>${fmtAmount(t.amount, t.currency)}</td>
                <td>
                    <span class="status-badge ${statusClass(t.status)}">${esc(t.status)}</span>
                    ${t.refunded ? '<span class="refunded-tag">Refunded</span>' : ''}
                </td>
                <td><span class="badge-date">${fmtDate(t.created)}</span></td>
            </tr>
        `).join('');
    }

    function showSkeleton() {
        document.getElementById('txn-tbody').innerHTML = Array.from({ length: perPage }, () => `
            <tr class="skeleton-row">
                <td><div class="skeleton-cell" style="width:160px"></div></td>
                <td><div class="skeleton-cell" style="width:140px"></div></td>
                <td><div class="skeleton-cell" style="width:70px"></div></td>
                <td><div class="skeleton-cell" style="width:120px"></div></td>
                <td><div class="skeleton-cell" style="width:70px"></div></td>
                <td><div class="skeleton-cell" style="width:70px"></div></td>
                <td><div class="skeleton-cell" style="width:90px"></div></td>
            </tr>`).join('');
    }

    function updatePaginationUI() {
        const total = allRows.length;
        const filterNote = isFiltered ? ' (filtered)' : '';
        document.getElementById('page-info').textContent =
            total ? `Showing ${total} transaction${total !== 1 ? 's' : ''}${filterNote}` : '';

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
            if (!emailFilter && !statusFilter && startingAfter) {
                params.set('starting_after', startingAfter);
            }

            const res  = await fetch(`${BASE}/list-transactions.php?${params}`);
            const data = await res.json();
            if (data.error) throw new Error(data.error);

            allRows    = data.transactions;
            hasMore    = data.has_more;
            isFiltered = !!data.filtered;

            renderRows(allRows);
            updatePaginationUI();
        } catch (e) {
            document.getElementById('txn-tbody').innerHTML =
                `<tr><td colspan="7"><div class="empty-state" style="color:var(--danger)">Error: ${esc(e.message)}</div></td></tr>`;
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

    loadPage();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
