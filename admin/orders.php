<?php
require_once '../config.php';
requireAdmin();
$page_title = 'Kelola Pesanan';
$message = '';
$error = '';

$allowed = ['pending','paid','shipped','completed','cancelled'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($order_id > 0 && in_array($status, $allowed, true)) {
        $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->execute([$status, $order_id]);
        $message = 'Status pesanan #' . $order_id . ' berhasil diperbarui.';
    } else {
        $error = 'Data status pesanan tidak valid.';
    }
}

// Filter berdasarkan user (jika ada)
$filter_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$filter_user = null;

if ($filter_user_id > 0) {
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? AND role = 'user'");
    $stmt->execute([$filter_user_id]);
    $filter_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Ambil pesanan
if ($filter_user) {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name user_name, u.email user_email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$filter_user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $orders = $pdo->query("
        SELECT o.*, u.name user_name, u.email user_email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// Ambil detail item untuk setiap pesanan
$order_items_map = [];
if ($orders) {
    $order_ids = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT oi.*, b.title, b.author
        FROM order_items oi
        JOIN books b ON oi.book_id = b.id
        WHERE oi.order_id IN ($placeholders)
        ORDER BY oi.order_id, oi.id
    ");
    $stmt->execute($order_ids);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as $item) {
        $order_items_map[$item['order_id']][] = $item;
    }
}

include '../includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <small class="fw-bold text-uppercase text-muted">Transaction Center</small>
        <h1 class="h3 mb-1">Kelola Pesanan</h1>
        <?php if ($filter_user): ?>
            <p class="text-muted mb-0">
                Menampilkan pesanan dari:
                <strong><?php echo sanitize($filter_user['name']); ?></strong>
                (<?php echo sanitize($filter_user['email']); ?>)
            </p>
        <?php else: ?>
            <p class="text-muted mb-0">Pantau metode pembayaran dan detail buku dari setiap pelanggan.</p>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2">
        <?php if ($filter_user): ?>
            <a href="orders.php" class="btn btn-outline-primary">Lihat Semua Pesanan</a>
        <?php endif; ?>
        <a href="users.php" class="btn btn-outline-secondary">Daftar User</a>
        <a href="index.php" class="btn btn-secondary">← Dashboard</a>
    </div>
</div>

<?php if ($message): ?><div class="alert alert-success"><?php echo sanitize($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo sanitize($error); ?></div><?php endif; ?>

<?php if (empty($orders)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <h3 class="h5">Belum ada pesanan</h3>
            <p class="mb-0"><?php echo $filter_user ? 'User ini belum pernah memesan buku.' : 'Pesanan akan muncul di sini setelah pelanggan checkout.'; ?></p>
        </div>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-4">
        <?php foreach ($orders as $order): ?>
            <?php $items = $order_items_map[$order['id']] ?? []; ?>
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div>
                        <strong class="fs-5">Pesanan #<?php echo $order['id']; ?></strong>
                        <div class="text-muted small">
                            <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                            · <?php echo sanitize($order['payment_method']); ?>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <?php echo statusBadge($order['status']); ?>
                        <strong class="text-primary"><?php echo formatRupiah($order['total']); ?></strong>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Pelanggan</small>
                            <strong><?php echo sanitize($order['user_name']); ?></strong><br>
                            <span class="text-muted"><?php echo sanitize($order['user_email']); ?></span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Ubah Status</small>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="action" value="status">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="status" class="form-select form-select-sm" style="max-width:160px">
                                    <?php foreach ($allowed as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo $order['status']===$s?'selected':''; ?>>
                                            <?php echo ucfirst($s); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-success" type="submit">Simpan</button>
                            </form>
                        </div>
                    </div>

                    <h6 class="mb-2">Detail Buku yang Dipesan</h6>
                    <?php if (empty($items)): ?>
                        <p class="text-muted mb-0">Tidak ada item ditemukan.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Judul Buku</th>
                                        <th>Pengarang</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Harga Satuan</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo sanitize($item['title']); ?></td>
                                        <td><?php echo sanitize($item['author']); ?></td>
                                        <td class="text-center"><?php echo (int)$item['quantity']; ?></td>
                                        <td class="text-end"><?php echo formatRupiah($item['price']); ?></td>
                                        <td class="text-end"><b><?php echo formatRupiah($item['price'] * $item['quantity']); ?></b></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>
