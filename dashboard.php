<?php
$pageTitle    = 'Dashboard – Stripe';
$pageHeading  = 'Dashboard';
$pageSubtitle = 'Overview of your Stripe customers, subscriptions, and payments.';
$currentPage  = 'dashboard';
$pageCss      = 'dashboard.css';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

    <div class="stat-grid" id="stat-grid">
        <a class="stat-card stat-card-link" href="<?= APP_BASE ?>/customers.php">
            <div class="stat-card-top">
                <span class="stat-label">Customers</span>
                <div class="stat-icon customers">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                </div>
            </div>
            <div class="stat-value loading" id="stat-customers"><div class="stat-skeleton"></div></div>
        </a>
        <a class="stat-card stat-card-link" href="<?= APP_BASE ?>/subscriptions.php">
            <div class="stat-card-top">
                <span class="stat-label">Active Subscriptions</span>
                <div class="stat-icon subscriptions">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </div>
            </div>
            <div class="stat-value loading" id="stat-subscriptions"><div class="stat-skeleton"></div></div>
        </a>
        <a class="stat-card stat-card-link" href="<?= APP_BASE ?>/plans.php">
            <div class="stat-card-top">
                <span class="stat-label">Plans</span>
                <div class="stat-icon plans">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </div>
            </div>
            <div class="stat-value loading" id="stat-plans"><div class="stat-skeleton"></div></div>
        </a>
        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-label">Total Collected</span>
                <div class="stat-icon revenue">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
            </div>
            <div class="stat-value loading" id="stat-revenue"><div class="stat-skeleton"></div></div>
            <div class="stat-note" id="stat-revenue-note"></div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="section dashboard-panel">
            <div class="section-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Recent Transactions
            </div>
            <div class="table-wrap">
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="recent-charges-tbody">
                        <tr><td colspan="4"><div class="dashboard-empty">Loading…</div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section dashboard-panel">
            <div class="section-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                Recent Subscriptions
            </div>
            <div class="table-wrap">
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="recent-subs-tbody">
                        <tr><td colspan="4"><div class="dashboard-empty">Loading…</div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Quick Actions
        </div>
        <div class="quick-links">
            <a class="quick-link" href="<?= APP_BASE ?>/customers.php">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                Add Customer
            </a>
            <a class="quick-link" href="<?= APP_BASE ?>/plans.php">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                Create Plan
            </a>
            <a class="quick-link" href="<?= APP_BASE ?>/subscriptions.php">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                Subscriptions
            </a>
            <a class="quick-link" href="<?= APP_BASE ?>/transactions.php">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Transactions
            </a>
            <a class="quick-link" href="<?= APP_BASE ?>/invoices.php">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Invoices
            </a>
        </div>
    </div>

</div>
</main>

<script>
    function fmtDate(ts) {
        if (!ts) return '—';
        return new Date(ts * 1000).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function fmtAmount(amount, currency) {
        if (!amount) return '—';
        return `${(amount / 100).toFixed(2)} ${currency}`;
    }

    function fmtCount(data) {
        return data.has_more ? `${data.count}+` : String(data.count);
    }

    function statusClass(status) {
        const known = ['active', 'succeeded', 'paid', 'canceled', 'pending', 'failed', 'past_due', 'trialing', 'open'];
        return known.includes(status) ? `status-${status}` : 'status-default';
    }

    function esc(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function setStat(id, text) {
        const el = document.getElementById(id);
        el.textContent = text;
        el.classList.remove('loading');
    }

    function renderCharges(rows) {
        const tbody = document.getElementById('recent-charges-tbody');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="4"><div class="dashboard-empty">No transactions yet.</div></td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(r => `
            <tr>
                <td>${esc(r.customer_name || r.customer_email || '—')}</td>
                <td>${fmtAmount(r.amount, r.currency)}</td>
                <td><span class="status-badge ${statusClass(r.status)}">${esc(r.status)}</span></td>
                <td><span class="badge-date">${fmtDate(r.created)}</span></td>
            </tr>
        `).join('');
    }

    function renderSubs(rows) {
        const tbody = document.getElementById('recent-subs-tbody');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="4"><div class="dashboard-empty">No subscriptions yet.</div></td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(s => `
            <tr>
                <td>${esc(s.customer_name || s.customer_email || '—')}</td>
                <td>${s.amount ? fmtAmount(s.amount, s.currency) + ' / ' + esc(s.interval) : '—'}</td>
                <td><span class="status-badge ${statusClass(s.status)}">${esc(s.status.replace('_', ' '))}</span></td>
                <td><span class="badge-date">${fmtDate(s.created)}</span></td>
            </tr>
        `).join('');
    }

    async function loadDashboard() {
        try {
            const res  = await fetch(`${BASE}/list-dashboard-stats.php`);
            const data = await res.json();
            if (data.error) throw new Error(data.error);

            const { stats } = data;
            setStat('stat-customers', fmtCount(stats.customers));
            setStat('stat-subscriptions', fmtCount(stats.subscriptions));
            setStat('stat-plans', fmtCount(stats.plans));
            setStat('stat-revenue', fmtAmount(stats.revenue.amount, stats.revenue.currency || 'USD'));

            const note = document.getElementById('stat-revenue-note');
            note.textContent = stats.revenue.has_more ? 'From succeeded charges (500+)' : 'From succeeded charges';

            renderCharges(data.recent_charges);
            renderSubs(data.recent_subscriptions);
        } catch (e) {
            ['stat-customers', 'stat-subscriptions', 'stat-plans', 'stat-revenue'].forEach(id => {
                setStat(id, '—');
            });
            document.getElementById('recent-charges-tbody').innerHTML =
                `<tr><td colspan="4"><div class="dashboard-empty" style="color:var(--danger)">Error: ${esc(e.message)}</div></td></tr>`;
            document.getElementById('recent-subs-tbody').innerHTML =
                `<tr><td colspan="4"><div class="dashboard-empty" style="color:var(--danger)">Error: ${esc(e.message)}</div></td></tr>`;
        }
    }

    loadDashboard();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
