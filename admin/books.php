<?php
require_once '../config.php';
requireAdmin();

$page_title = 'Kelola Buku';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $title = sanitize($_POST['title'] ?? '');
        $author = sanitize($_POST['author'] ?? '');
        $publisher = sanitize($_POST['publisher'] ?? '');
        $year = (int)($_POST['year'] ?? 0);
        $isbn = sanitize($_POST['isbn'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $category = sanitize($_POST['category'] ?? 'Fiksi');
        $cover_name = null;

        // Upload cover buku (JPG, PNG, atau WEBP; maksimal 3 MB).
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Gagal mengunggah cover. Silakan coba lagi.';
            } elseif ($_FILES['cover']['size'] > 3 * 1024 * 1024) {
                $error = 'Ukuran cover maksimal 3 MB.';
            } else {
                $image_info = @getimagesize($_FILES['cover']['tmp_name']);
                $mime = $image_info['mime'] ?? '';
                $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!$image_info || !isset($extensions[$mime])) {
                    $error = 'Format cover harus JPG, PNG, atau WEBP.';
                } else {
                    $upload_dir = dirname(__DIR__) . '/assets/covers/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $cover_name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                    if (!move_uploaded_file($_FILES['cover']['tmp_name'], $upload_dir . $cover_name)) {
                        $error = 'Cover gagal disimpan ke server.';
                        $cover_name = null;
                    }
                }
            }
        }
        $allowed_categories = ['Fiksi', 'Ilmiah', 'Romens', 'Novel', '18+', 'Pengetahuan'];

        if (!in_array($category, $allowed_categories, true)) {
            $category = 'Fiksi';
        }

        if ($error !== '') {
            // Pesan kesalahan upload sudah disiapkan di atas.
        } elseif (empty($title) || empty($author) || $price <= 0) {
            $error = 'Judul, pengarang, dan harga harus diisi.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO books (title, author, publisher, year, isbn, price, stock, description, category, cover) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $author, $publisher, $year ?: null, $isbn ?: null, $price, $stock, $description, $category, $cover_name])) {
                $message = 'Buku berhasil ditambahkan.';
            } else {
                $error = 'Gagal menambahkan buku.';
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $book_id = (int)$_POST['book_id'];
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        if ($stmt->execute([$book_id])) {
            $message = 'Buku berhasil dihapus.';
        } else {
            $error = 'Gagal menghapus buku.';
        }
    }
}

$stmt = $pdo->query("SELECT * FROM books ORDER BY created_at DESC");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<h1 class="h3 mb-4">Kelola Buku</h1>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Tambah Buku Baru</h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="title" class="form-label">Judul:</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="author" class="form-label">Pengarang:</label>
                        <input type="text" class="form-control" id="author" name="author" required>
                    </div>
                    <div class="mb-3">
                        <label for="publisher" class="form-label">Penerbit:</label>
                        <input type="text" class="form-control" id="publisher" name="publisher">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="year" class="form-label">Tahun:</label>
                            <input type="number" class="form-control" id="year" name="year">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="isbn" class="form-label">ISBN:</label>
                            <input type="text" class="form-control" id="isbn" name="isbn">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Kategori:</label>
                        <select class="form-select" id="category" name="category" required>
                            <?php foreach (['Fiksi', 'Ilmiah', 'Romens', 'Novel', '18+', 'Pengetahuan'] as $category): ?>
                                <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="price" class="form-label">Harga:</label>
                            <input type="number" class="form-control" id="price" name="price" step="1000" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="stock" class="form-label">Stok:</label>
                            <input type="number" class="form-control" id="stock" name="stock" value="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="cover" class="form-label">Gambar Cover Buku (opsional):</label>
                        <input type="file" class="form-control" id="cover" name="cover" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <div class="form-text">Pilih JPG, PNG, atau WEBP. Ukuran maksimal 3 MB.</div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi:</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Tambah Buku</button>
                </form>
            </div>
        </div>
    </div>
</div>

<h2 class="h4 mt-5 mb-3">Daftar Buku</h2>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Cover</th>
                    <th>Judul</th>
                    <th>Pengarang</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td><?php echo $book['id']; ?></td>
                        <td>
                            <?php if (!empty($book['cover'])): ?>
                                <img src="<?php echo APP_URL; ?>/assets/covers/<?php echo rawurlencode(basename($book['cover'])); ?>" alt="Cover buku" width="55" height="75" class="rounded object-fit-cover">
                            <?php else: ?>📖<?php endif; ?>
                        </td>
                        <td><?php echo sanitize($book['title']); ?></td>
                        <td><?php echo sanitize($book['author']); ?></td>
                        <td><span class="badge text-bg-info"><?php echo sanitize($book['category'] ?? 'Fiksi'); ?></span></td>
                        <td><?php echo formatRupiah($book['price']); ?></td>
                        <td><?php echo $book['stock']; ?></td>
                        <td>
                            <form method="POST" action="" onsubmit="return confirm('Hapus buku ini?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
