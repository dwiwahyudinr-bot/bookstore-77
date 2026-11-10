<?php
/**
 * admin/messages.php - Kotak masuk pesan "Hubungi Admin" dari user.
 * Admin dapat menandai pesan sudah/belum dibaca dan menghapusnya.
 */
require_once '../config.php';
requireAdmin();

$page_title = 'Pesan Masuk';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'read' || $action === 'unread') {
        $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = ? WHERE id = ?");
        $stmt->execute([$action === 'read' ? 1 : 0, $id]);
        $message = 'Status pesan diperbarui.';
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Pesan berhasil dihapus.';
    } else {
        $error = 'Aksi tidak valid.';
    }
}

// Pesan yang belum dibaca ditampilkan lebih dulu.
$messages = $pdo->query("SELECT * FROM contact_messages ORDER BY is_read ASC, created_at DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Pesan dari User</h1>
    <a href="index.php" class="btn btn-secondary">← Dashboard</a>
</div>

<?php if ($message): ?><div class="alert alert-success"><?php echo sanitize($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo sanitize($error); ?></div><?php endif; ?>

<?php if (!$messages): ?>
    <div class="text-center text-muted py-5"><h2 class="h5">Belum ada pesan masuk.</h2></div>
<?php endif; ?>

<?php foreach ($messages as $msg): ?>
    <div class="card shadow-sm mb-3 <?php echo $msg['is_read'] ? '' : 'border-primary'; ?>">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong><?php echo sanitize($msg['subject']); ?></strong>
                <?php if (!$msg['is_read']): ?><span class="badge text-bg-primary ms-1">Baru</span><?php endif; ?>
            </div>
            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></small>
        </div>
        <div class="card-body">
            <p class="small text-muted mb-2">
                Dari: <strong><?php echo sanitize($msg['name']); ?></strong>
                (<a href="mailto:<?php echo sanitize($msg['email']); ?>"><?php echo sanitize($msg['email']); ?></a>)
            </p>
            <p><?php echo nl2br(sanitize($msg['message'])); ?></p>
            <div class="d-flex gap-2">
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$msg['id']; ?>">
                    <input type="hidden" name="action" value="<?php echo $msg['is_read'] ? 'unread' : 'read'; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-primary"><?php echo $msg['is_read'] ? 'Tandai Belum Dibaca' : 'Tandai Sudah Dibaca'; ?></button>
                </form>
                <form method="POST" action="" onsubmit="return confirm('Hapus pesan ini?')">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$msg['id']; ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php include '../includes/footer.php'; ?>
