<?php
require_once 'config.php';

$page_title = 'Beranda';
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$categories = ['Fiksi', 'Ilmiah', 'Romens', 'Novel', '18+', 'Pengetahuan'];

$sql = "SELECT * FROM books WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (title LIKE ? OR author LIKE ? OR publisher LIKE ?)";
    $term = '%' . $search . '%';
    $params = [$term, $term, $term];
}
if ($category !== '' && in_array($category, $categories, true)) {
    $sql .= " AND category = ?";
    $params[] = $category;
}
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="bg-primary text-white text-center rounded-3 py-5 px-3 mb-4">
    <h1 class="display-6 fw-bold">Selamat Datang di <?php echo APP_NAME; ?></h1>
    <p class="mb-0">Temukan buku favoritmu dengan pencarian dan kategori yang lebih mudah.</p>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="index.php" class="row g-2 mb-3">
            <div class="col-12 col-md">
                <input type="text" class="form-control form-control-lg" name="search" value="<?php echo sanitize($search); ?>" placeholder="Cari judul, pengarang, atau penerbit...">
            </div>
            <?php if ($category !== ''): ?><input type="hidden" name="category" value="<?php echo sanitize($category); ?>"><?php endif; ?>
            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-primary btn-lg w-100">🔎 Cari Buku</button>
            </div>
        </form>

        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-sm rounded-pill <?php echo $category === '' ? 'btn-primary' : 'btn-outline-primary'; ?>" href="index.php<?php echo $search !== '' ? '?search=' . urlencode($search) : ''; ?>">Semua</a>
            <?php foreach ($categories as $item): ?>
                <a class="btn btn-sm rounded-pill <?php echo $category === $item ? 'btn-primary' : 'btn-outline-primary'; ?>" href="index.php?category=<?php echo urlencode($item); ?><?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>"><?php echo sanitize($item); ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
    <h2 class="h4 mb-0"><?php echo $category ? 'Kategori: ' . sanitize($category) : 'Katalog Buku'; ?></h2>
    <span class="text-muted"><?php echo count($books); ?> buku ditemukan</span>
</div>

<?php if (empty($books)): ?>
    <div class="text-center text-muted py-5">
        <h3 class="h4">Buku tidak ditemukan</h3>
        <p>Coba kata kunci atau kategori lainnya.</p>
    </div>
<?php else: ?>
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">
    <?php foreach ($books as $book): ?>
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="ratio bg-body-secondary" style="--bs-aspect-ratio: 135%;">
                    <div class="d-flex align-items-center justify-content-center">
                        <?php if (!empty($book['cover'])): ?>
                            <img src="<?php echo APP_URL; ?>/assets/covers/<?php echo sanitize($book['cover']); ?>" class="card-img-top w-100 h-100 object-fit-cover" alt="<?php echo sanitize($book['title']); ?>" onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                        <?php endif; ?>
                        <span class="display-1 text-secondary <?php echo !empty($book['cover']) ? 'd-none' : ''; ?>">📖</span>
                    </div>
                </div>
                <div class="card-body d-flex flex-column">
                    <div><span class="badge text-bg-info mb-2"><?php echo sanitize($book['category'] ?? 'Fiksi'); ?></span></div>
                    <h5 class="card-title"><?php echo sanitize($book['title']); ?></h5>
                    <p class="card-subtitle text-muted small mb-2"><?php echo sanitize($book['author']); ?></p>
                    <p class="fs-5 fw-bold text-danger mb-1"><?php echo formatRupiah($book['price']); ?></p>
                    <p class="small mb-3 <?php echo $book['stock'] > 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo $book['stock'] > 0 ? "Stok: {$book['stock']}" : 'Stok habis'; ?>
                    </p>
                    <div class="d-flex flex-wrap gap-2 mt-auto">
                        <a href="book.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary">Lihat Detail</a>
                        <?php if ($book['stock'] > 0): ?>
                        <form method="POST" action="cart.php">
                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="action" value="add">
                            <button type="submit" class="btn btn-sm btn-success">+ Keranjang</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
