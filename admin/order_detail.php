<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch order info
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name as customer_name, u.email as customer_email,
           ua.recipient_name, ua.phone as customer_phone, ua.address_line1, ua.district, ua.city
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    LEFT JOIN user_addresses ua ON o.address_id = ua.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    set_flash("error", "Không tìm thấy đơn hàng.");
    header("Location: orders.php");
    exit;
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*
    FROM order_items oi
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .status-badge { padding: 6px 12px; border-radius: 50px; font-size: 12px; font-weight: 600; }
    .status-dang_cho { background: #fff7e6; color: #faad14; border: 1px solid #ffe58f; }
    .status-da_xac_nhan { background: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; }
    .status-dang_xu_ly { background: #f9f0ff; color: #722ed1; border: 1px solid #d3adf7; }
    .status-da_gui { background: #e6fffb; color: #13c2c2; border: 1px solid #87e8de; }
    .status-da_giao { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
    .status-hoan_thanh { background: #f0f5ff; color: #2f54eb; border: 1px solid #adc6ff; }
    .status-huy { background: #fff1f0; color: #f5222d; border: 1px solid #ffa39e; }

    .timeline-modern { position: relative; padding-left: 30px; }
    .timeline-modern::before { content: ''; position: absolute; left: 7px; top: 5px; bottom: 5px; width: 2px; background: #f0f0f0; }
    .timeline-item { position: relative; margin-bottom: 25px; }
    .timeline-dot { position: absolute; left: -30px; width: 16px; height: 16px; border-radius: 50%; background: #fff; border: 3px solid #ddd; z-index: 1; }
    .timeline-item.active .timeline-dot { border-color: #2c3e50; background: #2c3e50; }
    .timeline-content { background: #f8f9fa; padding: 12px 16px; border-radius: 12px; }
</style>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="orders.php" class="text-decoration-none">Đơn hàng</a></li>
                    <li class="breadcrumb-item active">Chi tiết đơn hàng #<?php echo $order['id']; ?></li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">Đơn hàng #<?php echo $order['id']; ?></h4>
                    <span class="status-badge status-<?php echo $order['order_status']; ?>">
                        <?php 
                        $status_map = [
                            'dang_cho' => 'Chờ xác nhận',
                            'da_xac_nhan' => 'Đã xác nhận',
                            'dang_xu_ly' => 'Đang xử lý',
                            'da_gui' => 'Đang giao hàng',
                            'da_giao' => 'Đã giao hàng',
                            'hoan_thanh' => 'Hoàn thành',
                            'huy' => 'Đã hủy'
                        ];
                        echo $status_map[$order['order_status']] ?? $order['order_status'];
                        ?>
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-white border shadow-sm btn-sm px-3 rounded-3"><i class="bi bi-printer me-2"></i>In hóa đơn</button>
                    <button class="btn btn-primary shadow-sm btn-sm px-3 rounded-3"><i class="bi bi-truck me-2"></i>Giao hàng</button>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Order Items -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2 text-primary"></i>Sản phẩm trong đơn</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-muted small fw-bold">Sản phẩm</th>
                                    <th class="py-3 text-muted small fw-bold text-center">Giá</th>
                                    <th class="py-3 text-muted small fw-bold text-center">Số lượng</th>
                                    <th class="py-3 text-muted small fw-bold text-end pe-4">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $it): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-light rounded p-1 me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-laptop fs-4 text-muted"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($it['product_name']); ?></div>
                                                    <div class="text-muted x-small">SKU: <?php echo htmlspecialchars($it['sku'] ?: 'N/A'); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center"><?php echo number_format($it['unit_price'], 0, ',', '.'); ?>đ</td>
                                        <td class="text-center"><?php echo $it['quantity']; ?></td>
                                        <td class="text-end pe-4 fw-bold"><?php echo number_format($it['subtotal'], 0, ',', '.'); ?>đ</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white p-4 border-top">
                        <div class="row justify-content-end">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Tạm tính:</span>
                                    <span class="fw-bold"><?php echo number_format($order['subtotal'], 0, ',', '.'); ?>đ</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Phí vận chuyển:</span>
                                    <span><?php echo number_format($order['shipping_fee'], 0, ',', '.'); ?>đ</span>
                                </div>
                                <?php if ($order['shipping_discount'] > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span class="small">Voucher vận chuyển:</span>
                                    <span class="small">-<?php echo number_format($order['shipping_discount'], 0, ',', '.'); ?>đ</span>
                                </div>
                                <?php endif; ?>
                                <?php if ($order['discount'] > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-danger">
                                    <span class="small">Voucher giảm giá:</span>
                                    <span class="small">-<?php echo number_format($order['discount'], 0, ',', '.'); ?>đ</span>
                                </div>
                                <?php endif; ?>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold text-dark">Tổng cộng:</span>
                                    <span class="fw-bold text-primary fs-4"><?php echo number_format($order['total'], 0, ',', '.'); ?>đ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Timeline -->
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Lịch sử đơn hàng</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="timeline-modern">
                            <div class="timeline-item active">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="small fw-bold text-dark">Đơn hàng đã được tạo</div>
                                        <div class="x-small text-muted"><?php echo date('H:i d/m/Y', strtotime($order['created_at'])); ?></div>
                                    </div>
                                    <div class="x-small text-muted">Khách hàng đã đặt hàng thành công từ hệ thống.</div>
                                </div>
                            </div>
                            <?php if($order['order_status'] != 'dang_cho'): ?>
                            <div class="timeline-item active">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="small fw-bold text-dark">Đã xác nhận đơn hàng</div>
                                        <div class="x-small text-muted"><?php echo date('H:i d/m/Y', strtotime($order['updated_at'])); ?></div>
                                    </div>
                                    <div class="x-small text-muted">Nhân viên đã kiểm tra và xác nhận đơn hàng.</div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Customer Info -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-person me-2 text-primary"></i>Thông tin khách hàng</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-light rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                <i class="bi bi-person fs-3 text-primary"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($order['customer_name'] ?: 'Khách vãng lai'); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($order['customer_email'] ?: 'N/A'); ?></div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="text-muted x-small fw-bold text-uppercase mb-2 d-block">Địa chỉ nhận hàng</label>
                            <div class="p-3 bg-light rounded-3">
                                <div class="small fw-bold text-dark mb-1"><?php echo htmlspecialchars($order['recipient_name']); ?></div>
                                <div class="small text-primary fw-bold mb-2"><i class="bi bi-telephone me-2"></i><?php echo htmlspecialchars($order['customer_phone']); ?></div>
                                <div class="small text-muted lh-sm">
                                    <?php echo htmlspecialchars($order['address_line1']); ?>, 
                                    <?php echo htmlspecialchars($order['district']); ?>, 
                                    <?php echo htmlspecialchars($order['city']); ?>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="text-muted x-small fw-bold text-uppercase mb-2 d-block">Ghi chú khách hàng</label>
                            <div class="small text-muted p-3 border rounded-3 bg-white">
                                <?php echo $order['notes'] ? nl2br(htmlspecialchars($order['notes'])) : '<span class="fst-italic">Không có ghi chú</span>'; ?>
                            </div>
                        </div>

                        <div>
                            <label class="text-muted x-small fw-bold text-uppercase mb-2 d-block">Thanh toán</label>
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 text-success rounded p-2 me-2">
                                    <i class="bi bi-wallet2"></i>
                                </div>
                                <div class="small fw-bold text-dark">
                                    <?php echo $order['payment_method'] === 'cod' ? 'Thanh toán khi nhận hàng (COD)' : 'Chuyển khoản ngân hàng'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Status Update -->
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-gear me-2 text-primary"></i>Cập nhật trạng thái</h6>
                    </div>
                    <div class="card-body p-4">
                        <form action="update_order_status.php" method="POST">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Trạng thái đơn hàng</label>
                                <select name="status" class="form-select form-select-lg fs-6 rounded-3">
                                    <option value="dang_cho" <?php echo $order['order_status'] == 'dang_cho' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                    <option value="da_xac_nhan" <?php echo $order['order_status'] == 'da_xac_nhan' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                    <option value="dang_xu_ly" <?php echo $order['order_status'] == 'dang_xu_ly' ? 'selected' : ''; ?>>Đang xử lý</option>
                                    <option value="da_gui" <?php echo $order['order_status'] == 'da_gui' ? 'selected' : ''; ?>>Đang giao hàng</option>
                                    <option value="da_giao" <?php echo $order['order_status'] == 'da_giao' ? 'selected' : ''; ?>>Đã giao hàng</option>
                                    <option value="hoan_thanh" <?php echo $order['order_status'] == 'hoan_thanh' ? 'selected' : ''; ?>>Hoàn thành</option>
                                    <option value="huy" <?php echo $order['order_status'] == 'huy' ? 'selected' : ''; ?>>Đã hủy</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Ghi chú nội bộ</label>
                                <textarea name="note" class="form-control rounded-3" rows="3" placeholder="Ghi chú cho nhân viên..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 rounded-3 shadow-sm">
                                <i class="bi bi-check2-circle me-2"></i>Cập nhật ngay
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
