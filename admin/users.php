<?php
require_once '../config.php';
requireAdmin();

$page_title = 'User Terdaftar';
$message = '';
$error = '';

// Hapus user (opsional, hanya non-admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if ($user_id > 0) {
        // Pastikan bukan admin
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && $user['role'] !== 'admin') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $message = 'User berhasil dihapus.';
            } else {
                $error = 'Gagal menghapus user.';
            }
        } else {
            $error = 'Tidak dapat menghapus akun admin.';
        }
    }
}

// Ambil semua user (kecuali admin) beserta jumlah pesanan mereka
$users = $pdo->query("
    SELECT u.id, u.name, u.email, u.role, u.created_at,
           COUNT(o.id) AS total_orders,
           COALESCE(SUM(CASE WHEN o.status IN ('paid','shipped','completed') THEN o.total ELSE 0 END), 0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id
    WHERE u.role = 'user'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <small class="fw-bold text-uppercase text-muted">User Management</small>
        <h1 class="h3 mb-1">User Terdaftar</h1>
        <p class="text-muted mb-0">Daftar pelanggan yang sudah mendaftar di toko buku.</p>
    </div>
    <a href="index.php" class="btn btn-secondary">← Dashboard</a>
</div>

<?php if ($message): ?><div class="alert alert-success"><?php echo sanitize($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo sanitize($error); ?></div><?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="text-center text-muted py-5">
                <h3 class="h5">Belum ada user terdaftar</h3>
                <p class="mb-0">User akan muncul di sini setelah mereka mendaftar.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Tanggal Daftar</th>
                            <th>Jumlah Pesanan</th>
                            <th>Total Belanja</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><b>#<?php echo $user['id']; ?></b></td>
                            <td><?php echo sanitize($user['name']); ?></td>
                            <td><?php echo sanitize($user['email']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <span class="badge text-bg-primary"><?php echo (int)$user['total_orders']; ?> pesanan</span>
                            </td>
                            <td><b><?php echo formatRupiah($user['total_spent']); ?></b></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="orders.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        Lihat Pesanan
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Yakin ingin menghapus user ini? Pesanan terkait juga akan terhapus.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
