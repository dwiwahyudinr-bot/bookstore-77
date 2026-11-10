<?php
/**
 * contact.php - Form "Hubungi Admin".
 * User yang sudah login mengirim pesan ke admin; pesan disimpan di tabel
 * contact_messages dan dibaca admin lewat Admin Panel > Pesan Masuk.
 */
require_once 'config.php';
requireLogin();

$page_title = 'Hubungi Admin';
$error = '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($subject === '' || $message === '') {
        $error = 'Subjek dan pesan harus diisi.';
    } elseif (mb_strlen($subject) > 150) {
        $error = 'Subjek maksimal 150 karakter.';
    } elseif (mb_strlen($message) < 10 || mb_strlen($message) > 2000) {
        $error = 'Pesan harus 10 - 2000 karakter.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (user_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['name'], $_SESSION['email'], $subject, $message]);

        $_SESSION['flash_message'] = 'Pesan Anda berhasil dikirim ke admin. Terima kasih!';
        $_SESSION['flash_type'] = 'success';
        header('Location: ' . APP_URL . '/contact.php');
        exit;
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-1">Hubungi Admin</h1>
                <p class="text-muted">Ada pertanyaan tentang buku atau pesanan? Kirim pesan di sini.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo sanitize($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" value="<?php echo sanitize($_SESSION['name']); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo sanitize($_SESSION['email']); ?>" disabled>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subjek:</label>
                        <input type="text" class="form-control" id="subject" name="subject" maxlength="150" required value="<?php echo sanitize($subject); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Pesan:</label>
                        <textarea class="form-control" id="message" name="message" rows="5" maxlength="2000" required><?php echo sanitize($message); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Kirim Pesan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
