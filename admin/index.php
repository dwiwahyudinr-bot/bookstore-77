<?php
require_once '../config.php';
requireAdmin();
$page_title = 'Dashboard Admin';

$totalBooks = (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalPending = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$unreadMessages = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ('paid','shipped','completed')")->fetchColumn();
$latest = $pdo->query("SELECT o.id, o.total, o.status, o.payment_method, o.created_at, u.name FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>
<div class="bg-primary text-white rounded-4 p-4 p-md-5 mb-4 shadow">
    <div>
        <small class="fw-bold text-uppercase opacity-75">Bookstore Admin</small>
        <h1 class="h2 my-1">Dashboard Admin</h1>
        <p class="mb-0 opacity-75">Kelola buku, kategori, stok, pesanan, dan pembayaran dari satu tempat.</p>
    </div>
</div>

<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-3 mb-4">
    <div class="col">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <small class="text-muted d-block">Total Buku</small>
                <strong class="fs-5"><?php echo $totalBooks; ?></strong>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <small class="text-muted d-block">Total Pesanan</small>
                <strong class="fs-5"><?php echo $totalOrders; ?></strong>
            </div>
        </div>
    </div>
    <div class="col">
        <a href="users.php" class="card h-100 shadow-sm text-decoration-none text-dark">
            <div class="card-body">
                <small class="text-muted d-block">User Terdaftar</small>
                <strong class="fs-5"><?php echo $totalUsers; ?></strong>
            </div>
        </a>
    </div>
    <div class="col">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <small class="text-muted d-block">Menunggu</small>
                <strong class="fs-5"><?php echo $totalPending; ?></strong>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <small class="text-muted d-block">Penjualan Selesai</small>
                <strong class="fs-5"><?php echo formatRupiah($totalRevenue); ?></strong>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-6">
        <section class="card h-100 shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-0">Menu Pengelolaan</h2>
                <p class="text-muted mb-3">Akses cepat fitur admin.</p>
                <div class="row row-cols-1 row-cols-sm-2 g-3">
                    <div class="col">
                        <a class="card h-100 text-decoration-none" href="books.php">
                            <div class="card-body">
                                <b class="d-block text-dark">Kelola Buku</b>
                                <small class="text-muted">Tambah, edit, hapus, kategori &amp; stok</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a class="card h-100 text-decoration-none" href="orders.php">
                            <div class="card-body">
                                <b class="d-block text-dark">Kelola Pesanan</b>
                                <small class="text-muted">Cek detail buku &amp; ubah status</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a class="card h-100 text-decoration-none" href="users.php">
                            <div class="card-body">
                                <b class="d-block text-dark">User Terdaftar</b>
                                <small class="text-muted">Lihat daftar pelanggan &amp; pesanannya</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a class="card h-100 text-decoration-none" href="messages.php">
                            <div class="card-body">
                                <b class="d-block text-dark">Pesan Masuk <?php if ($unreadMessages > 0): ?><span class="badge rounded-pill text-bg-danger"><?php echo $unreadMessages; ?></span><?php endif; ?></b>
                                <small class="text-muted">Pesan dari user (Hubungi Admin)</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a class="card h-100 text-decoration-none" href="../index.php">
                            <div class="card-body">
                                <b class="d-block text-dark">Lihat Toko</b>
                                <small class="text-muted">Kembali ke katalog pelanggan</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a class="card h-100 text-decoration-none" href="../cart.php">
                            <div class="card-body">
                                <b class="d-block text-dark">Keranjang</b>
                                <small class="text-muted">Melihat tampilan keranjang toko</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-lg-6">
        <section class="card h-100 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h2 class="h5 mb-0">Pesanan Terbaru</h2>
                        <p class="text-muted mb-0"><a href="users.php" class="text-decoration-none"><?php echo $totalUsers; ?> pengguna terdaftar</a>.</p>
                    </div>
                    <a href="orders.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                </div>
                <?php if (!$latest): ?>
                    <div class="text-center text-muted py-4"><h3 class="h5">Belum ada pesanan</h3></div>
                <?php endif; ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($latest as $order): ?>
                        <li class="list-group-item d-flex justify-content-between gap-3 px-0">
                            <div>
                                <b>#<?php echo $order['id']; ?> · <?php echo sanitize($order['name']); ?></b>
                                <small class="d-block text-muted"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?> · <?php echo sanitize($order['payment_method']); ?></small>
                            </div>
                            <div class="text-end">
                                <strong class="d-block mb-1"><?php echo formatRupiah($order['total']); ?></strong>
                                <?php echo statusBadge($order['status']); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
