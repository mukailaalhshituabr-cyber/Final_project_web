<?php
require_once '../../config.php';
require_once '../../includes/classes/Database.php';
require_once '../../includes/classes/Review.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tailor') {
    header('Location: ../auth/login.php');
    exit();
}

$tailorId = $_SESSION['user_id'];
$review = new Review();

// Get all reviews for this tailor
$reviews = $review->getReviewsByTailor($tailorId);

// Calculate average rating
$totalRating = 0;
$ratingCounts = [
    5 => 0,
    4 => 0,
    3 => 0,
    2 => 0,
    1 => 0
];

foreach ($reviews as $rev) {
    $totalRating += $rev['rating'];
    $ratingCounts[$rev['rating']]++;
}

$averageRating = count($reviews) > 0 ? $totalRating / count($reviews) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - Tailor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .rating-stars {
            color: #ffc107;
        }
        
        .review-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: transform 0.2s;
        }
        
        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .customer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .rating-progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        
        .rating-progress-bar {
            height: 100%;
            background-color: #ffc107;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Rating Overview -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold"><?php echo number_format($averageRating, 1); ?></h2>
                        <div class="rating-stars mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= floor($averageRating)): ?>
                                    <i class="bi bi-star-fill"></i>
                                <?php elseif ($i - 0.5 <= $averageRating): ?>
                                    <i class="bi bi-star-half"></i>
                                <?php else: ?>
                                    <i class="bi bi-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <p class="text-muted">Based on <?php echo count($reviews); ?> reviews</p>
                        
                        <!-- Rating Breakdown -->
                        <div class="mt-4">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <div class="d-flex align-items-center mb-2">
                                    <div class="me-2"><?php echo $i; ?> <i class="bi bi-star-fill text-warning"></i></div>
                                    <div class="rating-progress flex-grow-1 me-2">
                                        <?php 
                                        $percentage = count($reviews) > 0 ? ($ratingCounts[$i] / count($reviews)) * 100 : 0;
                                        ?>
                                        <div class="rating-progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <div class="text-muted small"><?php echo $ratingCounts[$i]; ?></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Reviews List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Customer Reviews</h5>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm w-auto">
                                <option value="all">All Reviews</option>
                                <option value="5">5 Stars</option>
                                <option value="4">4 Stars</option>
                                <option value="3">3 Stars</option>
                                <option value="2">2 Stars</option>
                                <option value="1">1 Star</option>
                            </select>
                            <button class="btn btn-sm btn-outline-primary" disabled>
                                <?php echo count($reviews); ?> Reviews
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($reviews)): ?>
                            <?php foreach ($reviews as $rev): ?>
                                <div class="review-card">
                                    <div class="d-flex mb-3">
                                        <img src="../../assets/images/avatars/<?php echo $rev['profile_pic'] ?: 'default.jpg'; ?>" 
                                             class="customer-avatar me-3" 
                                             alt="<?php echo htmlspecialchars($rev['customer_name']); ?>">
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($rev['customer_name']); ?></h6>
                                                    <div class="rating-stars small">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <?php if ($i <= $rev['rating']): ?>
                                                                <i class="bi bi-star-fill"></i>
                                                            <?php else: ?>
                                                                <i class="bi bi-star"></i>
                                                            <?php endif; ?>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                <small class="text-muted">
                                                    <?php echo date('M d, Y', strtotime($rev['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <h6><?php echo htmlspecialchars($rev['title']); ?></h6>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                                    
                                    <?php if (!empty($rev['response'])): ?>
                                        <div class="mt-3 p-3 bg-light rounded">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="bi bi-reply-fill text-primary me-2"></i>
                                                <strong>Your Response</strong>
                                                <small class="text-muted ms-auto">
                                                    <?php echo date('M d, Y', strtotime($rev['response_date'])); ?>
                                                </small>
                                            </div>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($rev['response'])); ?></p>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-3">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#responseModal<?php echo $rev['id']; ?>">
                                                <i class="bi bi-reply"></i> Respond
                                            </button>
                                        </div>
                                        
                                        <!-- Response Modal -->
                                        <div class="modal fade" id="responseModal<?php echo $rev['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="../../api/reviews.php">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Respond to Review</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="review_id" value="<?php echo $rev['id']; ?>">
                                                            <input type="hidden" name="action" value="respond">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Your Response</label>
                                                                <textarea class="form-control" name="response" rows="4" 
                                                                          placeholder="Write a professional and helpful response..." required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Submit Response</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-chat-square-text display-6 text-muted mb-3"></i>
                                <h5>No reviews yet</h5>
                                <p class="text-muted">Customer reviews will appear here after they rate your services.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter reviews by rating
        document.querySelector('select').addEventListener('change', function() {
            const rating = this.value;
            const reviews = document.querySelectorAll('.review-card');
            
            reviews.forEach(review => {
                const reviewRating = review.querySelector('.rating-stars').children.length;
                if (rating === 'all' || reviewRating == rating) {
                    review.style.display = 'block';
                } else {
                    review.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>