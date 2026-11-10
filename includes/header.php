<?php
if (!function_exists('statusBadge')) {
    function statusBadge($status) {
        $map = [
            'pending'   => 'bg-warning text-dark',
            'paid'      => 'bg-info text-dark',
            'shipped'   => 'bg-primary',
            'completed' => 'bg-success',
            'cancelled' => 'bg-danger',
        ];
        $class = $map[$status] ?? 'bg-secondary';
        return '<span class="badge rounded-pill ' . $class . '">' . ucfirst(htmlspecialchars($status)) . '</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo APP_URL; ?>/index.php"><?php echo APP_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/contact.php">Hubungi Admin</a></li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/admin/">Admin Panel</a></li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/cart.php">Keranjang
                                <span class="badge rounded-pill text-bg-danger"><?php echo isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?></span>
                            </a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/logout.php">Logout (<?php echo sanitize($_SESSION['name']); ?>)</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-4 flex-grow-1">
        <?php
        if (isset($_SESSION['flash_message'])) {
            echo '<div class="alert alert-' . $_SESSION['flash_type'] . ' alert-dismissible fade show" role="alert">' . $_SESSION['flash_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
        }
        ?>
