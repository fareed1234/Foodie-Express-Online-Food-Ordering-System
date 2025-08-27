<?php
$page_title = "Unauthorized Access";
include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card text-center">
        <h2 class="text-danger mb-4">Unauthorized Access</h2>
        <p>You don't have permission to access this page.</p>
        <div class="mt-4">
            <a href="login.php" class="btn btn-custom">Login</a>
            <a href="index.html" class="btn btn-outline-secondary">Home</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
