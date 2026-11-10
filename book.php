<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$book_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$book_id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$page_title = $book['title'];

include 'includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <div class="row g-4">
            <div class="col-12 col-md-4">
                <div class="ratio bg-body-secondary rounded" style="--bs-aspect-ratio: 135%;">
                    <div class="d-flex align-items-center justify-content-center">
                        <?php if (!empty($book['cover'])): ?>
                            <img src="<?php echo APP_URL; ?>/assets/covers/<?php echo rawurlencode(basename($book['cover'])); ?>" alt="Cover <?php echo sanitize($book['title']); ?>" class="w-100 h-100 object-fit-contain rounded">
                        <?php else: ?>
                            <span class="display-1 text-secondary">📖</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-8">
                <h1 class="h2"><?php echo sanitize($book['title']); ?></h1>
                <p class="text-muted fs-5">Oleh: <?php echo sanitize($book['author']); ?></p>
                <p class="display-6 fw-bold text-danger"><?php echo formatRupiah($book['price']); ?></p>
                <p class="mb-4 <?php echo $book['stock'] > 0 ? 'text-success' : 'text-danger'; ?>">
                    <?php echo $book['stock'] > 0 ? "Stok tersedia: {$book['stock']}" : 'Stok habis'; ?>
                </p>
                <div class="mb-4">
                    <h3 class="h5">Deskripsi</h3>
                    <p class="text-secondary"><?php echo sanitize($book['description']); ?></p>
                </div>
                <?php if ($book['stock'] > 0): ?>
                    <form method="POST" action="cart.php">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3 col-6 col-sm-3">
                            <label for="quantity" class="form-label">Jumlah:</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="<?php echo $book['stock']; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Tambah ke Keranjang</button>
                    </form>
                <?php else: ?>
                    <button class="btn btn-secondary" disabled>Stok Habis</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
