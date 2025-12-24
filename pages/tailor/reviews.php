<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/includes/classes/Database.php';
require_once ROOT_PATH . '/includes/classes/Review.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tailor') {
    header('Location: ../auth/login.php');
    exit();
}

$db = Database::getInstance();
$reviewObj = new Review();
$tailorId = $_SESSION['user_id'];

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$rating = isset($_GET['rating']) ? intval($_GET['rating']) : null;
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Get reviews with filters
$reviews = $reviewObj->getTailorReviews($tailorId, [
    'filter' => $filter,
    'rating' => $rating,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'search' => $search,
    'page' => $_GET['page'] ?? 1,
    'per_page' => 10
]);

// Get review statistics
$stats = $reviewObj->getTailorReviewStats($tailorId);

// Handle review reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_review'])) {
    $reviewId = intval($_POST['review_id']);
    $reply = trim($_POST['reply']);
    
    if (!empty($reply)) {
        $result = $reviewObj->addReviewReply($reviewId, $tailorId, $reply);
        if ($result) {
            header('Location: reviews.php?replied=1');
            exit();
        }
    }
}

// Handle review visibility
if (isset($_GET['toggle_visibility'])) {
    $reviewId = intval($_GET['toggle_visibility']);
    $reviewObj->toggleReviewVisibility($reviewId, $tailorId);
    header('Location: reviews.php');
    exit();
}

// Handle delete review reply
if (isset($_GET['delete_reply'])) {
    $replyId = intval($_GET['delete_reply']);
    $reviewObj->deleteReviewReply($replyId, $tailorId);
    header('Location: reviews.php?deleted=1');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .reviews-container {
            min-height: calc(100vh - 200px);
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        .overall-rating {
            font-size: 3rem;
            font-weight: bold;
            color: #667eea;
            line-height: 1;
        }
        .rating-breakdown {
            margin-top: 1rem;
        }
        .rating-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            margin: 0.3rem 0;
            overflow: hidden;
        }
        .rating-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 4px;
        }
        .review-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid transparent;
        }
        .review-card.positive {
            border-left-color: #28a745;
        }
        .review-card.negative {
            border-left-color: #dc3545;
        }
        .review-card.neutral {
            border-left-color: #6c757d;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        .customer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .customer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }
        .review-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .review-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .reply-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            border-left: 3px solid #667eea;
        }
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .pagination-container {
            margin-top: 2rem;
        }
        .btn-filter {
            background: #667eea;
            color: white;
            border: none;
        }
        .btn-filter:hover {
            background: #5a67d8;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container reviews-container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3 fw-bold mb-0">Customer Reviews</h1>
                <p class="text-muted">Manage and respond to customer feedback</p>
            </div>
        </div>

        <?php if (isset($_GET['replied'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Reply submitted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Reply deleted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Review Statistics -->
            <div class="col-lg-4 mb-4">
                <div class="stats-card">
                    <div class="overall-rating mb-2">
                        <?php echo number_format($stats['average_rating'], 1); ?>
                    </div>
                    <div class="rating-stars mb-2">
                        <?php
                        $fullStars = floor($stats['average_rating']);
                        $halfStar = $stats['average_rating'] - $fullStars >= 0.5;
                        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                        
                        for ($i = 0; $i < $fullStars; $i++) {
                            echo '<i class="bi bi-star-fill"></i>';
                        }
                        if ($halfStar) {
                            echo '<i class="bi bi-star-half"></i>';
                        }
                        for ($i = 0; $i < $emptyStars; $i++) {
                            echo '<i class="bi bi-star"></i>';
                        }
                        ?>
                    </div>
                    <p class="text-muted mb-4">Based on <?php echo $stats['total_reviews']; ?> reviews</p>
                    
                    <div class="rating-breakdown">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <div class="d-flex align-items-center mb-2">
                            <div class="me-2" style="width: 40px;"><?php echo $i; ?> <i class="bi bi-star-fill"></i></div>
                            <div class="flex-grow-1">
                                <div class="rating-bar">
                                    <div class="rating-fill" style="width: <?php 
                                        echo $stats['total_reviews'] > 0 ? ($stats['rating_counts'][$i] / $stats['total_reviews'] * 100) : 0;
                                    ?>%"></div>
                                </div>
                            </div>
                            <div class="ms-2" style="width: 40px;"><?php echo $stats['rating_counts'][$i]; ?></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top">
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h4 mb-1"><?php echo $stats['with_replies']; ?></div>
                                    <small class="text-muted">Replied</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h4 mb-1"><?php echo $stats['visible']; ?></div>
                                    <small class="text-muted">Visible</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Card -->
                <div class="filter-card">
                    <h5 class="mb-3">Filter Reviews</h5>
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="filter">
                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Reviews</option>
                                <option value="replied" <?php echo $filter === 'replied' ? 'selected' : ''; ?>>Replied</option>
                                <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending Reply</option>
                                <option value="hidden" <?php echo $filter === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <select class="form-select" name="rating">
                                <option value="">All Ratings</option>
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php echo $rating === $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">From</label>
                                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">To</label>
                                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search in reviews...">
                        </div>
                        
                        <button type="submit" class="btn btn-filter w-100">
                            <i class="bi bi-funnel me-2"></i>Apply Filters
                        </button>
                        <a href="reviews.php" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="bi bi-x-circle me-2"></i>Clear Filters
                        </a>
                    </form>
                </div>
            </div>
            
            <!-- Reviews List -->
            <div class="col-lg-8">
                <?php if (!empty($reviews['reviews'])): ?>
                    <?php foreach ($reviews['reviews'] as $review): 
                        $reviewClass = '';
                        if ($review['rating'] >= 4) $reviewClass = 'positive';
                        elseif ($review['rating'] <= 2) $reviewClass = 'negative';
                        else $reviewClass = 'neutral';
                    ?>
                    <div class="review-card <?php echo $reviewClass; ?>">
                        <div class="review-header">
                            <div class="customer-info">
                                <?php if (!empty($review['customer_avatar'])): ?>
                                    <img src="<?php echo PROFILE_IMAGES_URL . $review['customer_avatar']; ?>" 
                                         class="customer-avatar" alt="Customer">
                                <?php else: ?>
                                    <div class="customer-avatar bg-light d-flex align-items-center justify-content-center">
                                        <i class="bi bi-person-fill" style="font-size: 1.5rem; color: #6c757d;"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($review['customer_name']); ?></h6>
                                    <div class="rating-stars">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $review['rating']) {
                                                echo '<i class="bi bi-star-fill"></i>';
                                            } else {
                                                echo '<i class="bi bi-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <small class="review-date d-block">
                                    <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </small>
                                <?php if (!empty($review['order_number'])): ?>
                                    <small class="text-muted">Order #<?php echo $review['order_number']; ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="review-content mb-3">
                            <p class="mb-2"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            <?php if (!empty($review['images'])): ?>
                                <div class="review-images mt-2">
                                    <?php foreach ($review['images'] as $image): ?>
                                        <img src="<?php echo REVIEW_IMAGES_URL . $image; ?>" 
                                             class="img-thumbnail me-2" style="width: 80px; height: 80px; object-fit: cover;">
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($review['reply'])): ?>
                            <div class="reply-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <strong>Your Reply</strong>
                                    <small class="text-muted">
                                        <?php echo date('M d, Y', strtotime($review['replied_at'])); ?>
                                    </small>
                                </div>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['reply'])); ?></p>
                                <div class="text-end mt-2">
                                    <a href="reviews.php?delete_reply=<?php echo $review['reply_id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Delete this reply?')">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Reply Form -->
                            <form method="POST" action="" class="reply-form">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                <div class="mb-2">
                                    <textarea class="form-control" name="reply" rows="2" 
                                              placeholder="Write a reply to this review..." required></textarea>
                                </div>
                                <div class="review-actions">
                                    <button type="submit" name="reply_review" class="btn btn-sm btn-primary">
                                        <i class="bi bi-reply me-1"></i> Post Reply
                                    </button>
                                    <a href="reviews.php?toggle_visibility=<?php echo $review['id']; ?>" 
                                       class="btn btn-sm btn-outline-<?php echo $review['is_visible'] ? 'warning' : 'success'; ?>"
                                       onclick="return confirm('<?php echo $review['is_visible'] ? 'Hide' : 'Show'; ?> this review?')">
                                        <i class="bi bi-eye<?php echo $review['is_visible'] ? '' : '-slash'; ?> me-1"></i>
                                        <?php echo $review['is_visible'] ? 'Hide' : 'Show'; ?>
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
                    <?php if ($reviews['total_pages'] > 1): ?>
                    <div class="pagination-container">
                        <nav aria-label="Reviews pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($reviews['current_page'] > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $reviews['current_page'] - 1; 
                                        echo $filter ? '&filter=' . urlencode($filter) : '';
                                        echo $rating ? '&rating=' . $rating : '';
                                        echo $date_from ? '&date_from=' . urlencode($date_from) : '';
                                        echo $date_to ? '&date_to=' . urlencode($date_to) : '';
                                        echo $search ? '&search=' . urlencode($search) : '';
                                    ?>">Previous</a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $reviews['total_pages']; $i++): ?>
                                <li class="page-item <?php echo $i == $reviews['current_page'] ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i;
                                        echo $filter ? '&filter=' . urlencode($filter) : '';
                                        echo $rating ? '&rating=' . $rating : '';
                                        echo $date_from ? '&date_from=' . urlencode($date_from) : '';
                                        echo $date_to ? '&date_to=' . urlencode($date_to) : '';
                                        echo $search ? '&search=' . urlencode($search) : '';
                                    ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($reviews['current_page'] < $reviews['total_pages']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $reviews['current_page'] + 1;
                                        echo $filter ? '&filter=' . urlencode($filter) : '';
                                        echo $rating ? '&rating=' . $rating : '';
                                        echo $date_from ? '&date_from=' . urlencode($date_from) : '';
                                        echo $date_to ? '&date_to=' . urlencode($date_to) : '';
                                        echo $search ? '&search=' . urlencode($search) : '';
                                    ?>">Next</a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-chat-square-text"></i>
                        <h4>No reviews found</h4>
                        <p class="text-muted">
                            <?php if ($filter !== 'all' || $rating || $date_from || $date_to || $search): ?>
                                Try adjusting your filters
                            <?php else: ?>
                                You haven't received any reviews yet
                            <?php endif; ?>
                        </p>
                        <?php if ($filter !== 'all' || $rating || $date_from || $date_to || $search): ?>
                            <a href="reviews.php" class="btn btn-primary mt-3">
                                <i class="bi bi-x-circle me-2"></i>Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-expand textarea for reply forms
        document.addEventListener('DOMContentLoaded', function() {
            const textareas = document.querySelectorAll('.reply-form textarea');
            textareas.forEach(textarea => {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            });
        });
    </script>
</body>
</html>