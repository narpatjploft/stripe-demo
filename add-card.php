<?php
require __DIR__ . '/includes/app.php';

$customerId = trim($_GET['customer_id'] ?? '');
if (!$customerId) {
    header('Location: ' . APP_BASE . '/customers.php');
    exit;
}

require __DIR__ . '/config.php';
try {
    $customer = \Stripe\Customer::retrieve($customerId);
    $customerName  = $customer->name  ?? '';
    $customerEmail = $customer->email ?? '';
} catch (Exception $e) {
    $customerName  = '';
    $customerEmail = '';
}
$customerLabel = $customerName ?: ($customerEmail ?: $customerId);

$pageTitle   = 'Payment Methods – ' . $customerLabel;
$pageHeading = 'Payment Methods';
$pageSubtitle = 'Manage saved cards and set a default for payments.';
$currentPage = 'customers';
$pageCss     = 'add-card.css';
$extraHead   = '<script src="https://js.stripe.com/v3/"></script>';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

    <div class="customer-banner">
        <div class="customer-avatar"><?= strtoupper(mb_substr($customerLabel, 0, 2)) ?></div>
        <div class="customer-info">
            <?php if ($customerName): ?><div class="cname"><?= htmlspecialchars($customerName) ?></div><?php endif; ?>
            <?php if ($customerEmail): ?><div class="cemail"><?= htmlspecialchars($customerEmail) ?></div><?php endif; ?>
            <div class="cid"><?= htmlspecialchars($customerId) ?></div>
        </div>
    </div>

    <!-- Saved Cards -->
    <div class="section">
        <div class="section-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            Saved Cards
        </div>
        <div id="cards-list">
            <div class="skeleton"></div>
            <div class="skeleton" style="opacity:.6"></div>
        </div>
    </div>

    <!-- Add Card -->
    <div class="section">
        <div class="section-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            Add New Card
        </div>
        <form id="card-form">
            <div id="card-element"></div>
            <button id="submit-button" class="btn btn-primary" type="submit">
                <span id="btn-text">Save Card</span>
            </button>
        </form>
    </div>

</div>
</main>

<div id="toast"></div>

<script>
    const CUSTOMER_ID = <?= json_encode($customerId) ?>;

    let stripe, cardElement, clientSecret;

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = 'show ' + type;
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.className = ''; }, 3500);
    }

    function brandClass(brand) {
        const map = { visa:'visa', mastercard:'mastercard', amex:'amex', discover:'discover' };
        return map[brand.toLowerCase()] || '';
    }

    function cardIcon(brand) {
        const cls = brandClass(brand);
        return `<div class="card-brand-icon ${cls}">${brand.substring(0,4)}</div>`;
    }

    function renderCards(cards) {
        const list = document.getElementById('cards-list');

        if (!cards.length) {
            list.innerHTML = `
                <div class="empty-state">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    No saved cards yet. Add one below.
                </div>`;
            return;
        }

        list.innerHTML = cards.map(card => `
            <div class="card-item ${card.is_default ? 'default-card' : ''}" id="card-${card.id}" data-id="${card.id}">
                ${cardIcon(card.brand)}
                <div class="card-info">
                    <div class="card-number">${card.brand} •••• ${card.last4}</div>
                    <div class="card-meta">Expires ${card.exp_month}/${card.exp_year}</div>
                </div>
                ${card.is_default ? '<span class="default-badge">Default</span>' : ''}
                <div class="card-actions">
                    ${!card.is_default ? `<button class="btn btn-default" onclick="setDefault('${card.id}', this)">Set Default</button>` : ''}
                    ${!card.is_default ? `<button class="btn btn-remove" onclick="removeCard('${card.id}', this)">Remove</button>` : ''}
                </div>
            </div>
        `).join('');
    }

    async function loadCards() {
        try {
            const res  = await fetch(`${BASE}/list-cards.php?customer_id=${encodeURIComponent(CUSTOMER_ID)}`);
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            renderCards(data.cards);
        } catch (e) {
            document.getElementById('cards-list').innerHTML =
                `<div class="empty-state" style="color:#e05c6a">Failed to load cards: ${e.message}</div>`;
        }
    }

    async function setDefault(pmId, btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span>';
        try {
            const res  = await fetch(`${BASE}/set-default-card.php`, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ payment_method_id: pmId, customer_id: CUSTOMER_ID }),
            });
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            showToast('Default card updated ✓');
            await loadCards();
        } catch (e) {
            showToast(e.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Set Default';
        }
    }

    async function removeCard(pmId, btn) {
        if (!confirm('Remove this card?')) return;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span>';
        try {
            const res  = await fetch(`${BASE}/remove-card.php`, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ payment_method_id: pmId }),
            });
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            showToast('Card removed ✓');
            const el = document.getElementById(`card-${pmId}`);
            if (el) {
                el.style.transition = 'opacity 0.3s, transform 0.3s';
                el.style.opacity    = '0';
                el.style.transform  = 'translateX(20px)';
                setTimeout(() => loadCards(), 320);
            } else {
                await loadCards();
            }
        } catch (e) {
            showToast(e.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Remove';
        }
    }

    async function init() {
        try {
            const res  = await fetch(`${BASE}/create-setup-intent.php?customer_id=${encodeURIComponent(CUSTOMER_ID)}`);
            const data = await res.json();
            if (data.error) throw new Error(data.error);

            stripe = Stripe(data.publishableKey);
            const elements = stripe.elements();
            cardElement = elements.create('card', {
                style: {
                    base: {
                        color: '#e2e8f0',
                        fontFamily: 'Inter, sans-serif',
                        fontSize: '15px',
                        '::placeholder': { color: '#7b85a6' },
                        iconColor: '#7b85a6',
                    },
                    invalid: { color: '#e05c6a' },
                },
            });
            cardElement.mount('#card-element');
            clientSecret = data.clientSecret;
        } catch (e) {
            showToast('Could not load Stripe: ' + e.message, 'error');
        }
    }

    document.getElementById('card-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const btn     = document.getElementById('submit-button');
        const btnText = document.getElementById('btn-text');
        btn.disabled  = true;
        btnText.innerHTML = '<span class="spinner"></span> Saving…';

        try {
            const result = await stripe.confirmCardSetup(clientSecret, {
                payment_method: {
                    card: cardElement,
                    billing_details: { name: 'Card Holder' },
                },
            }, { handleActions: false });

            if (result.error) throw new Error(result.error.message);

            const si = result.setupIntent;

            if (si.status === 'requires_action') {
                await fetch(`${BASE}/reject-setup-intent.php`, {
                    method : 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body   : JSON.stringify({ setup_intent_id: si.id }),
                });
                throw new Error('This card requires 3D Secure and cannot be added. Please use another card.');
            }

            if (si.status === 'succeeded') {
                showToast('Card added successfully ✓');
                cardElement.clear();
                await init();
                await loadCards();
                return;
            }

            throw new Error('Unexpected status: ' + si.status);
        } catch (err) {
            showToast(err.message, 'error');
        } finally {
            btn.disabled   = false;
            btnText.textContent = 'Save Card';
        }
    });

    loadCards();
    init();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
