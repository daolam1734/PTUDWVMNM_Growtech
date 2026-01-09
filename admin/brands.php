<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

// Handle Add or Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_brand']) || isset($_POST['update_brand']))) {
    $name = trim($_POST['name']);
    $brand_id = isset($_POST['brand_id']) ? (int)$_POST['brand_id'] : 0;
    $logo = $_POST['existing_logo'] ?? '';

    // Handle Logo Upload
    if (!empty($_FILES['logo_file']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/brands/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fileExt = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
        $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
        
        if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $uploadDir . $newFileName)) {
            $logo = '/uploads/brands/' . $newFileName;
        }
    } elseif (!empty($_POST['logo_url'])) {
        $logo = $_POST['logo_url'];
    }

    if ($brand_id > 0) {
        // Update
        $stmt = $pdo->prepare("UPDATE brands SET name = ?, logo = ? WHERE id = ?");
        $stmt->execute([$name, $logo, $brand_id]);
        set_flash("success", "Cập nhật thương hiệu thành công.");
    } else {
        // Add
        $stmt = $pdo->prepare("INSERT INTO brands (name, logo) VALUES (?, ?)");
        $stmt->execute([$name, $logo]);
        set_flash("success", "Thêm thương hiệu thành công.");
    }
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

<style>
:root {
    --primary-dark: #1e293b;
    --accent-blue: #3b82f6;
    --bg-light: #f8fafc;
    --text-muted: #64748b;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.admin-content {
    background-color: var(--bg-light);
    min-height: 100vh;
    padding: 2rem;
}

.card-modern {
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    transition: transform 0.2s ease;
}

.table-modern thead th {
    background-color: #f1f5f9;
    color: var(--primary-dark);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 700;
    padding: 1rem;
    border: none;
}

.table-modern tbody td {
    padding: 1rem;
    vertical-align: middle;
}

.brand-logo-preview {
    width: 48px;
    height: 48px;
    object-fit: contain;
    background: #fff;
    padding: 4px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.btn-action {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: var(--text-muted);
    transition: all 0.2s;
}

.btn-action:hover {
    background: var(--accent-blue);
    color: #fff;
    border-color: var(--accent-blue);
}

.btn-action.btn-delete:hover {
    background: #ef4444;
    border-color: #ef4444;
}
</style>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item small"><a href="dashboard.php" class="text-decoration-none text-muted">Bảng điều khiển</a></li>
                        <li class="breadcrumb-item small active" aria-current="page">Thương hiệu</li>
                    </ol>
                </nav>
                <h4 class="fw-bold text-dark mb-0">Quản Lý Thương Hiệu</h4>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-white border-0 shadow-sm rounded-pill px-3" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Làm mới
                </button>
            </div>
        </div>

        <div class="row g-4">
            <!-- Form Column -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold" id="form-title"><i class="bi bi-plus-circle me-2 text-primary"></i>Thêm thương hiệu mới</h6>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data" id="brand-form">
                            <input type="hidden" name="brand_id" id="brand_id" value="0">
                            <input type="hidden" name="existing_logo" id="existing_logo" value="">
                            
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted">TÊN THƯƠNG HIỆU</label>
                                <input type="text" name="name" id="brand_name" class="form-control border-0 bg-light rounded-pill px-3 shadow-none" placeholder="VD: Apple, Samsung..." required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-uppercase opacity-75">Logo (Tải lên hoặc URL)</label>
                                <ul class="nav nav-pills mb-3 nav-fill gap-2 p-1 bg-light rounded-pill small" id="logo-tab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active rounded-pill py-1 px-3" data-bs-toggle="pill" data-bs-target="#upload-tab" type="button">Tải lên</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link rounded-pill py-1 px-3" data-bs-toggle="pill" data-bs-target="#url-tab" type="button">Link URL</button>
                                    </li>
                                </ul>
                                <div class="tab-content border rounded-3 p-3">
                                    <div class="tab-pane fade show active" id="upload-tab">
                                        <input type="file" name="logo_file" id="logo_file" class="form-control form-control-sm" accept="image/*">
                                        <div class="form-text x-small">Hỗ trợ JPG, PNG, WEBP.</div>
                                    </div>
                                    <div class="tab-pane fade" id="url-tab">
                                        <input type="text" name="logo_url" id="brand_logo_url" class="form-control form-control-sm" placeholder="https://...">
                                    </div>
                                </div>
                                
                                <div id="logo-preview-container" class="mt-3 d-none">
                                    <label class="form-label small text-muted">Xem trước logo:</label>
                                    <div class="bg-light p-3 rounded-3 text-center border-dashed position-relative">
                                        <img id="logo-preview" src="" style="max-height: 80px; max-width: 100%; object-fit: contain;">
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 pt-2">
                                <button type="submit" name="add_brand" id="submit-btn" class="btn btn-primary py-2 fw-bold rounded-3 shadow-sm">
                                    <i class="bi bi-save me-2"></i>Lưu thương hiệu
                                </button>
                                <button type="button" id="cancel-btn" class="btn btn-light border py-2 fw-bold rounded-3 d-none">
                                    Hủy bỏ chỉnh sửa
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- List Column -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-award me-2 text-primary"></i>Danh sách thương hiệu</h6>
                        <span class="badge bg-soft-primary text-primary px-3 rounded-pill"><?php echo count($brands); ?> thương hiệu</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-modern align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4" style="width: 80px;">ID</th>
                                    <th>Logo</th>
                                    <th>Tên thương hiệu</th>
                                    <th class="text-center">Số sản phẩm</th>
                                    <th class="text-end pe-4">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($brands as $b): 
                                    // Count products
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE brand_id = ?");
                                    $stmt->execute([$b['id']]);
                                    $count = $stmt->fetchColumn();
                                ?>
                                    <tr>
                                        <td class="ps-4 text-muted small">#<?php echo $b['id']; ?></td>
                                        <td>
                                            <?php if ($b['logo']): 
                                                $logo_url = $b['logo'];
                                                if (strpos($logo_url, 'http') !== 0) {
                                                    $logo_url = BASE_URL . ltrim($logo_url, '/');
                                                }
                                            ?>
                                                <div class="bg-white border rounded p-1 d-flex align-items-center justify-content-center shadow-sm" style="width: 60px; height: 40px;">
                                                    <img src="<?php echo htmlspecialchars($logo_url); ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;" onerror="this.src='https://placehold.co/100x50?text=LOGO'">
                                                </div>
                                            <?php else: ?>
                                                <div class="bg-light border rounded text-muted x-small d-flex align-items-center justify-content-center" style="width: 60px; height: 40px;">N/A</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><div class="fw-bold text-dark"><?php echo htmlspecialchars($b['name']); ?></div></td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border px-3"><?php echo $count; ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary px-3 edit-brand-btn" 
                                                        data-id="<?php echo $b['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($b['name']); ?>"
                                                        data-logo="<?php echo htmlspecialchars($b['logo']); ?>"
                                                        title="Chỉnh sửa">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="?delete=<?php echo $b['id']; ?>" 
                                                   class="btn btn-outline-danger px-3 delete-confirm" 
                                                   title="Xóa">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($brands)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted bg-light">
                                            <i class="bi bi-award fs-1 d-block mb-2 opacity-25"></i>
                                            Chưa có dữ liệu thương hiệu nào.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); }
.border-dashed { border-style: dashed !important; }
.delete-confirm:hover { background-color: var(--bs-danger); color: white; }
</style>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    
    document.addEventListener('DOMContentLoaded', function() {
        // Edit Brand
        document.querySelectorAll('.edit-brand-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const logo = this.getAttribute('data-logo');

                document.getElementById('form-title').innerHTML = '<i class="bi bi-pencil-square me-2 text-warning"></i>Sửa thương hiệu';
                document.getElementById('brand_id').value = id;
                document.getElementById('brand_name').value = name;
                document.getElementById('existing_logo').value = logo;
                document.getElementById('submit-btn').name = 'update_brand';
                document.getElementById('submit-btn').innerHTML = '<i class="bi bi-check-circle me-2"></i>Cập nhật';
                document.getElementById('cancel-btn').classList.remove('d-none');

                if (logo) {
                    // Normalize preview URL
                    let previewUrl = logo;
                    if (logo.indexOf('http') !== 0) {
                        previewUrl = BASE_URL + logo.replace(/^\//, '');
                    }
                    document.getElementById('logo-preview').src = previewUrl;
                    document.getElementById('logo-preview-container').classList.remove('d-none');
                    
                    if (logo.startsWith('http')) {
                        const urlTabTrigger = document.querySelector('button[data-bs-target="#url-tab"]');
                        if (urlTabTrigger) {
                            const tab = new bootstrap.Tab(urlTabTrigger);
                            tab.show();
                        }
                        document.getElementById('brand_logo_url').value = logo;
                    } else {
                        const uploadTabTrigger = document.querySelector('button[data-bs-target="#upload-tab"]');
                        if (uploadTabTrigger) {
                            const tab = new bootstrap.Tab(uploadTabTrigger);
                            tab.show();
                        }
                    }
                } else {
                    document.getElementById('logo-preview-container').classList.add('d-none');
                }
                
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });

        // Cancel Edit
        document.getElementById('cancel-btn').addEventListener('click', function() {
            document.getElementById('form-title').innerHTML = '<i class="bi bi-plus-circle me-2 text-primary"></i>Thêm thương hiệu mới';
            document.getElementById('brand_id').value = '0';
            document.getElementById('brand-form').reset();
            document.getElementById('existing_logo').value = '';
            document.getElementById('submit-btn').name = 'add_brand';
            document.getElementById('submit-btn').innerHTML = '<i class="bi bi-save me-2"></i>Lưu thương hiệu';
            document.getElementById('logo-preview-container').classList.add('d-none');
            this.classList.add('d-none');
        });

        // Preview image locally
        document.getElementById('logo_file').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(ex) {
                    document.getElementById('logo-preview').src = ex.target.result;
                    document.getElementById('logo-preview-container').classList.remove('d-none');
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Delete confirmation
        document.querySelectorAll('.delete-confirm').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if(!confirm('Bạn có chắc muốn xóa thương hiệu này? Thao tác không thể hoàn tác!')) {
                    e.preventDefault();
                }
            });
        });
    });
</script>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
