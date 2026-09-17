<?php
require_once __DIR__ . '/config/database.php';

$drinks = db()->query("SELECT id, name, description, price FROM drinks WHERE active = 1 ORDER BY sort_order, name")->fetchAll();
$milkTypes = db()->query("SELECT id, name FROM milk_types WHERE active = 1 ORDER BY sort_order, name")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Coffee Order</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="customer-page">
<header class="site-header">
    <div class="container nav">
        <div class="logo">COFFEE<span>ORDER</span></div>
        <div class="header-note">Fresh coffee. Made for you.</div>
    </div>
</header>

<main>
    <section class="hero customer-hero">
        <div class="container">
            <span class="eyebrow">Coffee Menu</span>
            <h1>What can we<br>make you?</h1>
            <p>Choose your drink, pick your milk, and we'll get it started.</p>
        </div>
    </section>

    <section class="container ordering-layout">
        <div class="menu-section">
            <?php if (!$drinks): ?>
                <div class="empty-menu">No drinks are available right now.</div>
            <?php else: ?>
                <div class="menu-grid customer-menu-grid">
                    <?php foreach ($drinks as $drink): ?>
                        <article class="card drink-card" data-drink-id="<?= (int)$drink['id'] ?>">
                            <div class="drink-card-top">
                                <h2><?= htmlspecialchars($drink['name']) ?></h2>
                                <div class="price">$<?= number_format((float)$drink['price'], 2) ?></div>
                            </div>
                            <p class="description"><?= htmlspecialchars($drink['description'] ?: 'A fresh coffee favorite.') ?></p>

                            <?php if ($milkTypes): ?>
                                <div class="drink-option">
                                    <label for="milk-<?= (int)$drink['id'] ?>">Milk</label>
                                    <select id="milk-<?= (int)$drink['id'] ?>" class="milk-select">
                                        <?php foreach ($milkTypes as $milk): ?>
                                            <option value="<?= (int)$milk['id'] ?>"><?= htmlspecialchars($milk['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <button type="button" class="btn btn-yellow add-to-order" data-drink-id="<?= (int)$drink['id'] ?>">
                                Add to Order
                            </button>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="order-panel" id="orderPanel">
            <div class="order-panel-head">
                <div>
                    <span class="eyebrow">Your Order</span>
                    <h2>Order Summary</h2>
                </div>
                <span class="cart-count" id="cartCount">0</span>
            </div>

            <div id="cartEmpty" class="cart-empty">
                <strong>Your order is empty.</strong>
                <span>Add a drink to get started.</span>
            </div>
            <div id="cartItems" class="order-list"></div>

            <div class="order-panel-footer">
                <div class="total-row">
                    <span>Total</span>
                    <strong id="cartTotal">$0.00</strong>
                </div>
                <button type="button" id="placeOrder" class="btn btn-yellow place-order" disabled>Place Order</button>
                <div id="orderError" class="order-error" role="alert"></div>
            </div>
        </aside>
    </section>
</main>

<div class="confirmation-overlay" id="confirmation" hidden>
    <div class="confirmation-card">
        <div class="confirmation-check">✓</div>
        <span class="eyebrow">Order Received</span>
        <h2>Order <span id="confirmationNumber">#0000</span></h2>
        <p>Your coffee is in the queue. We'll get it started.</p>
        <button type="button" class="btn btn-yellow" id="newOrder">Start Another Order</button>
    </div>
</div>

<script>
const drinks = <?= json_encode(array_map(fn($d) => [
    'id' => (int)$d['id'],
    'name' => $d['name'],
    'price' => (float)$d['price']
], $drinks), JSON_UNESCAPED_SLASHES) ?>;
const milkTypes = <?= json_encode(array_map(fn($m) => [
    'id' => (int)$m['id'],
    'name' => $m['name']
], $milkTypes), JSON_UNESCAPED_SLASHES) ?>;

let cart = [];

const money = value => '$' + Number(value).toFixed(2);
const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));

function addToOrder(drinkId, milkId) {
    const drink = drinks.find(d => d.id === drinkId);
    const milk = milkTypes.find(m => m.id === milkId) || null;
    if (!drink) return;

    const existing = cart.find(item => item.drink_id === drinkId && item.milk_type_id === (milk ? milk.id : null));
    if (existing) {
        existing.quantity++;
    } else {
        cart.push({
            drink_id: drink.id,
            drink_name: drink.name,
            price: drink.price,
            milk_type_id: milk ? milk.id : null,
            milk_name: milk ? milk.name : '',
            quantity: 1
        });
    }
    renderCart();
}

function changeQuantity(index, amount) {
    if (!cart[index]) return;
    cart[index].quantity += amount;
    if (cart[index].quantity <= 0) cart.splice(index, 1);
    renderCart();
}

function removeItem(index) {
    cart.splice(index, 1);
    renderCart();
}

function renderCart() {
    const itemsEl = document.getElementById('cartItems');
    const emptyEl = document.getElementById('cartEmpty');
    const button = document.getElementById('placeOrder');
    const count = cart.reduce((sum, item) => sum + item.quantity, 0);
    const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);

    document.getElementById('cartCount').textContent = count;
    document.getElementById('cartTotal').textContent = money(total);
    emptyEl.style.display = cart.length ? 'none' : 'flex';
    button.disabled = !cart.length;

    itemsEl.innerHTML = cart.map((item, index) => `
        <div class="cart-line">
            <div class="cart-line-info">
                <strong>${escapeHtml(item.drink_name)}</strong>
                ${item.milk_name ? `<span>${escapeHtml(item.milk_name)}</span>` : ''}
                <button type="button" class="remove-item" onclick="removeItem(${index})">Remove</button>
            </div>
            <div class="cart-line-right">
                <strong>${money(item.price * item.quantity)}</strong>
                <div class="qty-control">
                    <button type="button" aria-label="Decrease quantity" onclick="changeQuantity(${index}, -1)">−</button>
                    <span>${item.quantity}</span>
                    <button type="button" aria-label="Increase quantity" onclick="changeQuantity(${index}, 1)">+</button>
                </div>
            </div>
        </div>
    `).join('');
}

document.querySelectorAll('.add-to-order').forEach(button => {
    button.addEventListener('click', () => {
        const drinkId = Number(button.dataset.drinkId);
        const card = button.closest('.drink-card');
        const select = card.querySelector('.milk-select');
        const milkId = select ? Number(select.value) : null;
        addToOrder(drinkId, milkId);

        const original = button.textContent;
        button.textContent = 'Added ✓';
        setTimeout(() => button.textContent = original, 700);
    });
});

document.getElementById('placeOrder').addEventListener('click', async () => {
    if (!cart.length) return;
    const button = document.getElementById('placeOrder');
    const error = document.getElementById('orderError');
    error.textContent = '';
    button.disabled = true;
    button.textContent = 'Sending Order...';

    try {
        const response = await fetch('api/orders.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                items: cart.map(item => ({
                    drink_id: item.drink_id,
                    milk_type_id: item.milk_type_id,
                    quantity: item.quantity
                }))
            })
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.error || 'Could not place order.');

        document.getElementById('confirmationNumber').textContent = '#' + data.order_number;
        document.getElementById('confirmation').hidden = false;
        cart = [];
        renderCart();
    } catch (err) {
        error.textContent = err.message || 'Could not place order. Please try again.';
    } finally {
        button.textContent = 'Place Order';
        button.disabled = !cart.length;
    }
});

document.getElementById('newOrder').addEventListener('click', () => {
    document.getElementById('confirmation').hidden = true;
    window.scrollTo({top: 0, behavior: 'smooth'});
});

renderCart();
</script>
</body>
</html>
