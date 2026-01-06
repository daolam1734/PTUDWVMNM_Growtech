<?php
if (!isset($pdo)) require_once __DIR__ . "/../config/database.php";
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>
<style>
    .sidebar-title { font-size: 16px; font-weight: 700; margin-bottom: 15px; display: flex; align-items: center; color: var(--tet-red, #d32f2f); }
    .sidebar-title i { margin-right: 10px; }
    .sidebar-link { display: block; padding: 8px 0; color: rgba(0,0,0,.87); text-decoration: none; font-size: 14px; transition: all 0.2s; border-left: 3px solid transparent; padding-left: 10px; }
    .sidebar-link:hover { color: var(--tet-red, #d32f2f); border-left: 3px solid var(--tet-gold, #ffc107); background: rgba(211, 47, 47, 0.05); }
    .sidebar-link.active { color: var(--tet-red, #d32f2f); font-weight: 700; border-left: 3px solid var(--tet-red, #d32f2f); }
    .filter-group { border-bottom: 1px solid rgba(0,0,0,.05); padding-bottom: 20px; margin-bottom: 20px; }
    .btn-tet { background-color: var(--tet-red, #d32f2f); border: none; color: white; transition: all 0.3s; }
    .btn-tet:hover { background-color: var(--tet-dark-red, #b71c1c); transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
</style>

<form action="/weblaptop/index.php" method="GET">
    <?php if(isset($_GET['q'])): ?>
        <input type="hidden" name="q" value="<?php echo htmlspecialchars($_GET['q']); ?>">
    <?php endif; ?>
    <?php if(isset($_GET['category'])): ?>
        <input type="hidden" name="category" value="<?php echo htmlspecialchars($_GET['category']); ?>">
    <?php endif; ?>

    <div class="filter-group">
        <div class="sidebar-title"><span class="sparkle-effect"></span> Tất Cả Danh Mục</div>
        <a href="/weblaptop/index.php" class="sidebar-link <?php echo !isset($_GET["category"]) ? "active" : ""; ?>">
            Tất cả sản phẩm
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="/weblaptop/index.php?category=<?php echo $cat["slug"]; ?><?php echo isset($_GET['q']) ? '&q='.urlencode($_GET['q']) : ''; ?>" 
               class="sidebar-link <?php echo (isset($_GET["category"]) && $_GET["category"] == $cat["slug"]) ? "active" : ""; ?>">
                <?php echo htmlspecialchars($cat["name"]); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="filter-group">
        <div class="sidebar-title"><span class="sparkle-effect"></span> BỘ LỌC TÌM KIẾM</div>
        <div class="small fw-bold mb-2">Theo Thương Hiệu</div>
        <?php
        $brands = $pdo->query("SELECT name FROM brands")->fetchAll();
        $selected_brands = isset($_GET['brands']) ? (array)$_GET['brands'] : [];
        foreach ($brands as $b):
        ?>
            <div class="form-check mb-1">
                <input class="form-check-input" type="checkbox" name="brands[]" value="<?php echo $b["name"]; ?>" 
                       id="brand-<?php echo $b["name"]; ?>" <?php echo in_array($b["name"], $selected_brands) ? 'checked' : ''; ?>>
                <label class="form-check-label small" for="brand-<?php echo $b["name"]; ?>">
                    <?php echo htmlspecialchars($b["name"]); ?>
                </label>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="filter-group">
        <div class="small fw-bold mb-2">Khoảng Giá (VNĐ)</div>
        <div class="d-flex align-items-center gap-2 mb-3">
            <input type="number" name="min_price" class="form-control form-control-sm" placeholder="TỪ" 
                   value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>">
            <div style="width: 10px; height: 1px; background: #bdbdbd;"></div>
            <input type="number" name="max_price" class="form-control form-control-sm" placeholder="ĐẾN" 
                   value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>">
        </div>
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-tet btn-sm">ÁP DỤNG</button>
            <?php if(isset($_GET['brands']) || isset($_GET['min_price']) || isset($_GET['max_price']) || isset($_GET['category']) || isset($_GET['q'])): ?>
                <a href="/weblaptop/index.php" class="btn btn-outline-secondary btn-sm">XÓA TẤT CẢ</a>
            <?php endif; ?>
        </div>
    </div>
</form>
