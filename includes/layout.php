<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - MoneyFlow' : 'MoneyFlow'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <?php if(isset($pageContent)) echo $pageContent; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php
    // --- PERUBAHAN LOGIKA KRUSIAL DIMULAI DI SINI ---
    $currentPage = basename($_SERVER['PHP_SELF']);

    if ($currentPage == 'index.php') {
        echo '<script src="assets/js/main.js"></script>';
    }

    // Selalu muat script spesifik halaman jika ada
    if (isset($pageScript)) {
        echo $pageScript;
    }
    // --- AKHIR PERUBAHAN ---
    ?>
</body>
</html>