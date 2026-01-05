<?php
session_start();
require_once __DIR__ . "/functions.php";

// Mock news data (In a real app, this would come from a database)
$news_items = [
    [
        'id' => 1,
        'title' => 'Top 5 Laptop Gaming Đáng Mua Nhất Đầu Năm 2026',
        'summary' => 'Khám phá những mẫu laptop gaming mạnh mẽ nhất với card đồ họa RTX 50-series đang làm mưa làm gió trên thị trường.',
        'image' => 'https://images.unsplash.com/photo-1593642702821-c8da6771f0c6?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
        'date' => date('Y-m-d', strtotime('-1 day')),
        'category' => 'Đánh giá'
    ],
    [
        'id' => 2,
        'title' => 'Cách Vệ Sinh Laptop Tại Nhà Đơn Giản Và Hiệu Quả',
        'summary' => 'Hướng dẫn chi tiết cách vệ sinh màn hình, bàn phím và khe tản nhiệt để laptop luôn bền bỉ và hoạt động ổn định.',
        'image' => 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
        'date' => date('Y-m-d', strtotime('-3 days')),
        'category' => 'Thủ thuật'
    ],
    [
        'id' => 3,
        'title' => 'Intel Ra Mắt Chip Thế Hệ 16: Hiệu Năng Vượt Trội',
        'summary' => 'Intel chính thức giới thiệu dòng vi xử lý thế hệ mới với nhiều cải tiến về kiến trúc và khả năng xử lý đa nhiệm.',
        'image' => 'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
        'date' => date('Y-m-d', strtotime('-5 days')),
        'category' => 'Công nghệ'
    ],
    [
        'id' => 4,
        'title' => 'Sinh Viên Nên Chọn Laptop Nào Để Học Tập Và Làm Việc?',
        'summary' => 'Tư vấn chọn mua laptop phù hợp với nhu cầu học tập của sinh viên các ngành kinh tế, kỹ thuật và đồ họa.',
        'image' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
        'date' => date('Y-m-d', strtotime('-1 week')),
        'category' => 'Tư vấn'
    ],
    [
        'id' => 5,
        'title' => 'Xu Hướng Laptop Màn Hình OLED Trong Năm 2026',
        'summary' => 'Màn hình OLED đang dần trở nên phổ biến trên các dòng laptop từ tầm trung đến cao cấp nhờ chất lượng hiển thị tuyệt vời.',
        'image' => 'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
        'date' => date('Y-m-d', strtotime('-10 days')),
        'category' => 'Công nghệ'
    ],
    [
        'id' => 6,
        'title' => 'Đánh Giá Chi Tiết MacBook Air M5 Mới Nhất',
        'summary' => 'Liệu MacBook Air M5 có thực sự xứng đáng để nâng cấp từ phiên bản M3 hay M4? Hãy cùng tìm hiểu qua bài đánh giá này.',
        'image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
        'date' => date('Y-m-d', strtotime('-2 weeks')),
        'category' => 'Đánh giá'
    ]
];

include "includes/header.php";
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/weblaptop/index.php" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tin tức</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-9">
            <h2 class="mb-4 fw-bold border-start border-4 border-danger ps-3">Tin Tức Công Nghệ</h2>
            
            <div class="row g-4">
                <?php foreach ($news_items as $news): ?>
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm overflow-hidden news-card">
                        <div class="position-relative">
                            <img src="<?php echo $news['image']; ?>" class="card-img-top" alt="<?php echo $news['title']; ?>" style="height: 200px; object-fit: cover;">
                            <span class="position-absolute top-0 start-0 bg-danger text-white px-3 py-1 small fw-bold">
                                <?php echo $news['category']; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="text-muted small mb-2">
                                <i class="bi bi-calendar3 me-1"></i> <?php echo date('d/m/Y', strtotime($news['date'])); ?>
                            </div>
                            <h5 class="card-title fw-bold mb-3">
                                <a href="#" class="text-dark text-decoration-none stretched-link"><?php echo $news['title']; ?></a>
                            </h5>
                            <p class="card-text text-muted small">
                                <?php echo $news['summary']; ?>
                            </p>
                        </div>
                        <div class="card-footer bg-white border-0 pb-3">
                            <span class="text-danger fw-bold small">Xem chi tiết <i class="bi bi-arrow-right ms-1"></i></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <nav aria-label="Page navigation" class="mt-5">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Trước</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Sau</a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="sticky-top" style="top: 180px; z-index: 900;">
                <!-- Search Widget -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Tìm kiếm tin tức</h6>
                        <div class="input-group">
                            <input type="text" class="form-control border-end-0" placeholder="Nhập từ khóa...">
                            <button class="btn btn-outline-secondary border-start-0" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Categories Widget -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Danh mục</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><a href="#" class="text-decoration-none text-muted d-flex justify-content-between"><span>Đánh giá</span> <span class="badge bg-light text-dark">12</span></a></li>
                            <li class="mb-2"><a href="#" class="text-decoration-none text-muted d-flex justify-content-between"><span>Thủ thuật</span> <span class="badge bg-light text-dark">8</span></a></li>
                            <li class="mb-2"><a href="#" class="text-decoration-none text-muted d-flex justify-content-between"><span>Công nghệ</span> <span class="badge bg-light text-dark">15</span></a></li>
                            <li class="mb-2"><a href="#" class="text-decoration-none text-muted d-flex justify-content-between"><span>Tư vấn</span> <span class="badge bg-light text-dark">10</span></a></li>
                            <li><a href="#" class="text-decoration-none text-muted d-flex justify-content-between"><span>Khuyến mãi</span> <span class="badge bg-light text-dark">5</span></a></li>
                        </ul>
                    </div>
                </div>

                <!-- Popular Posts -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Tin xem nhiều</h6>
                        <div class="d-flex mb-3">
                            <img src="https://images.unsplash.com/photo-1593642702821-c8da6771f0c6?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80" class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                            <div>
                                <a href="#" class="text-dark text-decoration-none small fw-bold d-block mb-1">Top 5 Laptop Gaming 2026</a>
                                <span class="text-muted smaller" style="font-size: 0.75rem;"><?php echo date('d/m/Y', strtotime('-1 day')); ?></span>
                            </div>
                        </div>
                        <div class="d-flex mb-3">
                            <img src="https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80" class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                            <div>
                                <a href="#" class="text-dark text-decoration-none small fw-bold d-block mb-1">Cách Vệ Sinh Laptop Tại Nhà</a>
                                <span class="text-muted smaller" style="font-size: 0.75rem;"><?php echo date('d/m/Y', strtotime('-3 days')); ?></span>
                            </div>
                        </div>
                        <div class="d-flex">
                            <img src="https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80" class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                            <div>
                                <a href="#" class="text-dark text-decoration-none small fw-bold d-block mb-1">Intel Ra Mắt Chip Thế Hệ 16</a>
                                <span class="text-muted smaller" style="font-size: 0.75rem;"><?php echo date('d/m/Y', strtotime('-5 days')); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.news-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.news-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}
.smaller {
    font-size: 0.8rem;
}
</style>

<?php include "includes/footer.php"; ?>
