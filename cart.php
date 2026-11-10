<?php
require_once 'config.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $book_id = (int)$_POST['book_id'];
        $quantity = (int)$_POST['quantity'];

        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($book && $quantity > 0 && $quantity <= $book['stock']) {
            if (isset($_SESSION['cart'][$book_id])) {
                $_SESSION['cart'][$book_id] += $quantity;
            } else {
                $_SESSION['cart'][$book_id] = $quantity;
            }
            $_SESSION['flash_message'] = 'Buku berhasil ditambahkan ke keranjang!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Jumlah tidak valid atau stok tidak mencukupi.';
            $_SESSION['flash_type'] = 'danger';
        }
        header('Location: ' . APP_URL . '/cart.php');
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'remove') {
        $book_id = (int)$_POST['book_id'];
        unset($_SESSION['cart'][$book_id]);
        $_SESSION['flash_message'] = 'Buku berhasil dihapus dari keranjang.';
        $_SESSION['flash_type'] = 'success';
        header('Location: ' . APP_URL . '/cart.php');
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $book_id = (int)$_POST['book_id'];
        $quantity = (int)$_POST['quantity'];

        if ($quantity > 0) {
            $_SESSION['cart'][$book_id] = $quantity;
        } else {
            unset($_SESSION['cart'][$book_id]);
        }
        header('Location: ' . APP_URL . '/cart.php');
        exit;
    }
}

$page_title = 'Keranjang Belanja';

include 'includes/header.php';
?>

<h1 class="h3 mb-4">Keranjang Belanja</h1>

<?php if (empty($_SESSION['cart'])): ?>
    <div class="text-center text-muted py-5">
        <h3 class="h4">Keranjang belanja kosong</h3>
        <p>Silakan <a href="index.php">lihat katalog buku</a> untuk memulai berbelanja.</p>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Buku</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    foreach ($_SESSION['cart'] as $book_id => $quantity):
                        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
                        $stmt->execute([$book_id]);
                        $book = $stmt->fetch(PDO::FETCH_ASSOC);

                        if (!$book) continue;

                        $subtotal = $book['price'] * $quantity;
                        $total += $subtotal;
                    ?>
                        <tr>
                            <td><?php echo sanitize($book['title']); ?></td>
                            <td><?php echo formatRupiah($book['price']); ?></td>
                            <td>
                                <form method="POST" action="" class="d-flex gap-2">
                                    <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="number" class="form-control form-control-sm w-auto" name="quantity" value="<?php echo $quantity; ?>" min="1" max="<?php echo $book['stock']; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                </form>
                            </td>
                            <td><?php echo formatRupiah($subtotal); ?></td>
                            <td>
                                <form method="POST" action="" onsubmit="return confirm('Hapus buku ini dari keranjang?')">
                                    <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm mt-3">
        <div class="card-body text-end">
            <h3 class="h4 mb-3">Total: <?php echo formatRupiah($total); ?></h3>
            <a href="checkout.php" class="btn btn-primary">Checkout</a>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
