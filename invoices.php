<?php
$pageTitle      = 'Invoices – Stripe';
$pageHeading    = 'Invoices';
$pageSubtitle   = 'View all customer invoices and filter by email or status.';
$currentPage    = 'invoices';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

    <div class="section">
        <div class="table-toolbar">
            <div class="section-title" style="margin-bottom:0">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                All Invoices
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
                        <option value="paid">Paid</option>
                        <option value="open">Open</option>
                        <option value="draft">Draft</option>
                        <option value="void">Void</option>
                        <option value="uncollectible">Uncollectible</option>
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
                        <th>Invoice</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Period</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody id="inv-tbody"></tbody>
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

    function fmtPeriod(start, end) {
        if (!start && !end) return '—';
        return `${fmtDate(start)} – ${fmtDate(end)}`;
    }

    function statusClass(status) {
        const known = ['paid', 'open', 'draft', 'void', 'uncollectible'];
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
        const tbody = document.getElementById('inv-tbody');

        if (!rows.length) {
            tbody.innerHTML = `
                <tr><td colspan="7">
                    <div class="empty-state">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        No invoices found.
                    </div>
                </td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map(i => `
            <tr>
                <td>
                    <div class="customer-name-cell">
                        <div class="customer-avatar">${initials(i.customer_name, i.customer_email)}</div>
                        <div>
                            <div class="customer-name">${esc(i.customer_name) || '<span style="color:var(--muted)">—</span>'}</div>
                            <div class="inv-id">${esc(i.id)}</div>
                        </div>
                    </div>
                </td>
                <td><span class="badge-email">${esc(i.customer_email) || '<span style="color:var(--muted)">—</span>'}</span></td>
                <td>
                    <div class="inv-number">${esc(i.number) || '—'}</div>
                    ${i.subscription_id ? `<div class="inv-id">${esc(i.subscription_id)}</div>` : ''}
                </td>
                <td>${fmtAmount(i.amount, i.currency)}</td>
                <td><span class="status-badge ${statusClass(i.status)}">${esc(i.status)}</span></td>
                <td><span class="period-text">${fmtPeriod(i.period_start, i.period_end)}</span></td>
                <td><span class="badge-date">${fmtDate(i.created)}</span></td>
            </tr>
        `).join('');
    }

    function showSkeleton() {
        document.getElementById('inv-tbody').innerHTML = Array.from({ length: perPage }, () => `
            <tr class="skeleton-row">
                <td><div class="skeleton-cell" style="width:160px"></div></td>
                <td><div class="skeleton-cell" style="width:140px"></div></td>
                <td><div class="skeleton-cell" style="width:100px"></div></td>
                <td><div class="skeleton-cell" style="width:70px"></div></td>
                <td><div class="skeleton-cell" style="width:70px"></div></td>
                <td><div class="skeleton-cell" style="width:120px"></div></td>
                <td><div class="skeleton-cell" style="width:90px"></div></td>
            </tr>`).join('');
    }

    function updatePaginationUI() {
        const total = allRows.length;
        const filterNote = isFiltered ? ' (filtered)' : '';
        document.getElementById('page-info').textContent =
            total ? `Showing ${total} invoice${total !== 1 ? 's' : ''}${filterNote}` : '';

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

            const res  = await fetch(`${BASE}/list-invoices.php?${params}`);
            const data = await res.json();
            if (data.error) throw new Error(data.error);

            allRows    = data.invoices;
            hasMore    = data.has_more;
            isFiltered = !!data.filtered;

            renderRows(allRows);
            updatePaginationUI();
        } catch (e) {
            document.getElementById('inv-tbody').innerHTML =
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
