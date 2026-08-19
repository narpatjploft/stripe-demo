<?php
$pageTitle   = 'Plans – Stripe';
$pageHeading = 'Plans';
$pageSubtitle = 'Create and manage recurring billing plans.';
$currentPage = 'plans';
$pageCss     = 'plans.css';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

    <!-- Create Plan -->
    <div class="section">
        <div class="section-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            Create New Plan
        </div>
        <form id="create-plan-form" novalidate>
            <div class="form-grid">
                <div class="form-group">
                    <label for="plan-name">Plan Name</label>
                    <input type="text" id="plan-name" placeholder="e.g. Pro Monthly" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="plan-amount">Amount</label>
                    <div class="input-prefix-wrap">
                        <span>$</span>
                        <input type="number" id="plan-amount" placeholder="9.99" min="0.01" step="0.01">
                    </div>
                </div>
                <div class="form-group">
                    <label for="plan-interval">Billing Interval</label>
                    <select id="plan-interval">
                        <option value="month">Monthly</option>
                        <option value="year">Yearly</option>
                        <option value="week">Weekly</option>
                        <option value="day">Daily</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="plan-currency">Currency</label>
                    <select id="plan-currency">
                        <option value="usd">USD – US Dollar</option>
                        <option value="eur">EUR – Euro</option>
                        <option value="gbp">GBP – British Pound</option>
                        <option value="aud">AUD – Australian Dollar</option>
                        <option value="cad">CAD – Canadian Dollar</option>
                    </select>
                </div>
            </div>
            <div id="plan-error" style="color:var(--danger);font-size:0.83rem;margin-top:12px;display:none;"></div>
            <button type="submit" class="btn btn-primary" id="create-plan-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                <span id="create-plan-btn-text">Create Plan</span>
            </button>
        </form>
    </div>

    <!-- Plans List -->
    <div class="section">
        <div class="section-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            All Plans
        </div>
        <div id="plans-grid">
            <div class="skeleton-card"></div>
            <div class="skeleton-card" style="opacity:.7"></div>
            <div class="skeleton-card" style="opacity:.4"></div>
        </div>
    </div>

</div>
</main>

<div id="toast"></div>

<script>
    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = 'show ' + type;
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.className = ''; }, 3500);
    }

    function fmtAmount(cents, currency) {
        return (cents / 100).toLocaleString('en-US', { style: 'currency', currency });
    }

    function renderPlans(plans, newId = null) {
        const grid = document.getElementById('plans-grid');
        if (!plans.length) {
            grid.innerHTML = `
                <div class="empty-state">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    No plans yet. Create one above.
                </div>`;
            return;
        }
        grid.innerHTML = plans.map(p => `
            <div class="plan-card ${p.id === newId ? 'new-plan' : ''}" id="plan-${p.id}">
                <div class="plan-name">${p.name}</div>
                <div class="plan-price">
                    ${fmtAmount(p.amount, p.currency)}
                    <span>/ ${p.interval}</span>
                </div>
                <div class="plan-interval-badge">${p.currency} · ${p.interval}ly</div>
                <div class="plan-id">${p.id}</div>
            </div>`).join('');
    }

    async function loadPlans(newId = null) {
        try {
            const res  = await fetch(`${BASE}/list-plans.php`);
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            renderPlans(data.plans, newId);
        } catch (e) {
            document.getElementById('plans-grid').innerHTML =
                `<div class="empty-state" style="color:var(--danger)">Error: ${e.message}</div>`;
        }
    }

    document.getElementById('create-plan-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const name     = document.getElementById('plan-name').value.trim();
        const amountRaw = parseFloat(document.getElementById('plan-amount').value);
        const interval = document.getElementById('plan-interval').value;
        const currency = document.getElementById('plan-currency').value;
        const errEl    = document.getElementById('plan-error');

        errEl.style.display = 'none';

        if (!name) { errEl.textContent = 'Plan name is required.'; errEl.style.display = 'block'; return; }
        if (!amountRaw || amountRaw <= 0) { errEl.textContent = 'Enter a valid amount.'; errEl.style.display = 'block'; return; }

        const amount = Math.round(amountRaw * 100);

        const btn     = document.getElementById('create-plan-btn');
        const btnText = document.getElementById('create-plan-btn-text');
        btn.disabled  = true;
        btnText.innerHTML = '<span class="spinner"></span> Creating…';

        try {
            const res  = await fetch(`${BASE}/create-plan.php`, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ name, amount, interval, currency }),
            });
            const data = await res.json();
            if (data.error) throw new Error(data.error);

            showToast(`Plan "${data.plan.name}" created ✓`);
            document.getElementById('plan-name').value   = '';
            document.getElementById('plan-amount').value = '';
            await loadPlans(data.plan.id);
        } catch (err) {
            errEl.textContent   = err.message;
            errEl.style.display = 'block';
        } finally {
            btn.disabled        = false;
            btnText.textContent = 'Create Plan';
        }
    });

    loadPlans();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
