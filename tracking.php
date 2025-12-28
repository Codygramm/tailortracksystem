<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order - TailorTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/tracking.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-tshirt me-2"></i>
                <span>TailorTrack</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="tracking.php">Track Order</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="faq.php">FAQ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#contact">Contact</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-outline-light" href="login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="page-hero">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="hero-content">
                <h1 class="page-title">Track Your Order</h1>
                <p class="page-subtitle">Monitor your custom clothing order in real-time</p>
                <div class="breadcrumb-nav">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <span>/</span>
                    <span>Track Order</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Tracking Section -->
    <section class="tracking-section section-padding">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <!-- Tracking Card -->
                    <div class="tracking-card">
                        <div class="card-header">
                            <h2><i class="fas fa-search me-3"></i>Enter Order Details</h2>
                            <p>Please enter your order ID to check the current status</p>
                        </div>

                        <form id="tracking-form" class="tracking-form">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="orderId" class="form-label">
                                            <i class="fas fa-barcode me-2"></i>Order ID
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text"
                                               class="form-control form-control-lg"
                                               id="orderId"
                                               placeholder="e.g., TT-20240315-001"
                                               required>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Format: TT-YYYYMMDD-XXX
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-search me-2"></i> Track Order
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Results Section -->
                    <div id="tracking-result" class="tracking-result" style="display: none;">
                        <div class="tracking-status">
                            <!-- Results will be loaded here by JavaScript -->
                        </div>
                    </div>

                    <!-- Information Cards -->
                    <div class="row mt-5">
                        <div class="col-md-4 mb-4">
                            <div class="info-card">
                                <div class="info-icon">
                                    <i class="fas fa-receipt"></i>
                                </div>
                                <h5>Order Confirmation</h5>
                                <p>You received an order ID when you placed your order. Check your receipt or email.</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="info-card">
                                <div class="info-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h5>Processing Time</h5>
                                <p>Most custom orders are completed within 7-14 days depending on complexity.</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="info-card">
                                <div class="info-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <h5>Need Help?</h5>
                                <p>Contact us at +60 12-345 6789 if you have trouble tracking your order.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Status Guide -->
                    <div class="status-guide">
                        <h3 class="text-center mb-4">Understanding Order Status</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="status-item">
                                    <div class="status-badge pending">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <h6>Pending</h6>
                                        <p>Order received and waiting to be assigned to a tailor</p>
                                    </div>
                                </div>
                                <div class="status-item">
                                    <div class="status-badge assigned">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div>
                                        <h6>Assigned</h6>
                                        <p>A skilled tailor has been assigned to your order</p>
                                    </div>
                                </div>
                                <div class="status-item">
                                    <div class="status-badge in-progress">
                                        <i class="fas fa-cut"></i>
                                    </div>
                                    <div>
                                        <h6>In Progress</h6>
                                        <p>Your garment is being tailored with precision</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="status-item">
                                    <div class="status-badge completed">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div>
                                        <h6>Completed</h6>
                                        <p>Your order is ready for collection or delivery</p>
                                    </div>
                                </div>
                                <div class="status-item">
                                    <div class="status-badge paid">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div>
                                        <h6>Paid</h6>
                                        <p>Payment has been received and order is complete</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3><i class="fas fa-question-circle me-2"></i>Have Questions About Your Order?</h3>
                    <p class="mb-0">Visit our FAQ page for answers to common questions</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="faq.php" class="btn btn-light btn-lg">
                        <i class="fas fa-book me-2"></i>View FAQ
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><i class="fas fa-tshirt me-2"></i> TailorTrack</h5>
                    <p>Your trusted partner for custom clothing and professional tailoring services in Pontian, Johor.</p>
                </div>
                <div class="col-lg-2 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="tracking.php">Track Order</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="index.php#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h5>Services</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Baju Melayu</a></li>
                        <li><a href="#">Baju Kurung</a></li>
                        <li><a href="#">Kebaya</a></li>
                        <li><a href="#">Clothing Repair</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h5>Contact</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt me-2"></i> Jalan Taib 3, Pontian, Johor</li>
                        <li><i class="fas fa-phone me-2"></i> +60 12-345 6789</li>
                        <li><i class="fas fa-envelope me-2"></i> info@warisanewan.com</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; 2023 WARISAN EWAN NIAGA RESOURCES. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Designed for TailorTrack</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/tracking.js"></script>
</body>
</html>
