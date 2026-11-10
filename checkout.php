<?php
require_once 'config.php';
requireLogin();

if (empty($_SESSION['cart'])) {
    header('Location: ' . APP_URL . '/cart.php');
    exit;
}

$page_title = 'Checkout';
$payment_methods = [
    'Transfer Bank' => 'Transfer Bank',
    'E-Wallet' => 'E-Wallet (DANA/OVO/GoPay)',
    'COD' => 'COD (Bayar di Tempat)'
];

$total = 0;
foreach ($_SESSION['cart'] as $book_id => $quantity) {
    $stmt = $pdo->prepare("SELECT price, stock FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$book || $quantity > $book['stock']) {
        $_SESSION['flash_message'] = 'Ada jumlah buku di keranjang yang melebihi stok. Silakan perbarui keranjang.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . APP_URL . '/cart.php');
        exit;
    }
    $total += $book['price'] * $quantity;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = trim($_POST['payment_method'] ?? '');

    if (!array_key_exists($payment_method, $payment_methods)) {
        $_SESSION['flash_message'] = 'Silakan pilih metode pembayaran.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, payment_method) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $total, $payment_method]);
            $order_id = $pdo->lastInsertId();

            foreach ($_SESSION['cart'] as $book_id => $quantity) {
                $stmt = $pdo->prepare("SELECT price, stock FROM books WHERE id = ? FOR UPDATE");
                $stmt->execute([$book_id]);
                $book = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$book || $quantity > $book['stock']) {
                    throw new Exception('Stok buku tidak mencukupi.');
                }

                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $book_id, $quantity, $book['price']]);

                $stmt = $pdo->prepare("UPDATE books SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$quantity, $book_id]);
            }

            $pdo->commit();
            $_SESSION['cart'] = [];
            $_SESSION['flash_message'] = 'Pesanan #' . $order_id . ' berhasil dibuat dengan metode ' . $payment_methods[$payment_method] . '.';
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . APP_URL . '/index.php');
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_message'] = 'Terjadi kesalahan: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
    }
}

include 'includes/header.php';
?>

<h1 class="h3 mb-4">Checkout</h1>

<div class="row g-4">
    <div class="col-12 col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Metode Pembayaran</h2>
                <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin membuat pesanan ini?')">
                    <div class="list-group mb-3">
                        <?php foreach ($payment_methods as $key => $label): ?>
                            <label class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                                <input class="form-check-input mt-0" type="radio" name="payment_method" value="<?php echo sanitize($key); ?>" required>
                                <span><?php echo sanitize($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="fs-4 fw-bold text-danger">Total: <?php echo formatRupiah($total); ?></p>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-success">💳 Bayar / Buat Pesanan</button>
                        <a href="cart.php" class="btn btn-secondary">Kembali ke Keranjang</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Ringkasan Pesanan</h2>
                <ul class="list-group list-group-flush">
                    <?php foreach ($_SESSION['cart'] as $book_id => $quantity):
                        $stmt = $pdo->prepare("SELECT title, price FROM books WHERE id = ?");
                        $stmt->execute([$book_id]);
                        $book = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($book):
                    ?>
                        <li class="list-group-item d-flex justify-content-between gap-3 px-0">
                            <span><?php echo sanitize($book['title']); ?> × <?php echo $quantity; ?></span>
                            <strong><?php echo formatRupiah($book['price'] * $quantity); ?></strong>
                        </li>
                    <?php endif; endforeach; ?>
                </ul>
                <hr>
                <strong class="d-block text-end fs-5">Total <?php echo formatRupiah($total); ?></strong>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
