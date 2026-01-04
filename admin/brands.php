<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $name = trim($_POST['name']);
    $logo = $_POST['logo'];
    $stmt = $pdo->prepare("INSERT INTO brands (name, logo) VALUES (?, ?)");
    $stmt->execute([$name, $logo]);
    set_flash("success", "Thêm thương hiệu thành công.");
    header("Location: brands.php"); exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
    $stmt->execute([$id]);
    set_flash("success", "Xóa thương hiệu thành công.");
    header("Location: brands.php"); exit;
}

$brands = $pdo->query("SELECT * FROM brands ORDER BY id DESC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Quản lý thương hiệu</h2>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Thêm thương hiệu mới</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Tên thương hiệu</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL Logo</label>
                            <input type="text" name="logo" class="form-control" placeholder="https://...">
                        </div>
                        <button type="submit" name="add_brand" class="btn btn-primary w-100">Thêm mới</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Logo</th>
                                <th>Tên</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($brands as $b): ?>
                                <tr>
                                    <td><?php echo $b['id']; ?></td>
                                    <td>
                                        <?php if ($b['logo']): ?>
                                            <img src="<?php echo htmlspecialchars($b['logo']); ?>" height="30" class="border rounded">
                                        <?php else: ?>
                                            <span class="text-muted small">No Logo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($b['name']); ?></strong></td>
                                    <td>
                                        <a href="?delete=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa thương hiệu này?')">Xóa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
