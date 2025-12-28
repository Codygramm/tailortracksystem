<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Frequently Asked Questions - TailorTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/faq.css">
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
                        <a class="nav-link" href="tracking.php">Track Order</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="faq.php">FAQ</a>
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
                <h1 class="page-title">Frequently Asked Questions</h1>
                <p class="page-subtitle">Find answers to common questions about our tailoring services</p>
                <div class="breadcrumb-nav">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <span>/</span>
                    <span>FAQ</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <section class="search-section">
        <div class="container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="faqSearch" class="form-control" placeholder="Search for answers...">
            </div>
        </div>
    </section>

    <!-- FAQ Categories -->
    <section class="faq-section section-padding">
        <div class="container">
            <!-- Category Tabs -->
            <div class="category-tabs mb-5">
                <button class="category-btn active" data-category="all">
                    <i class="fas fa-th-large"></i> All Questions
                </button>
                <button class="category-btn" data-category="orders">
                    <i class="fas fa-shopping-cart"></i> Orders
                </button>
                <button class="category-btn" data-category="services">
                    <i class="fas fa-cut"></i> Services
                </button>
                <button class="category-btn" data-category="pricing">
                    <i class="fas fa-dollar-sign"></i> Pricing
                </button>
                <button class="category-btn" data-category="delivery">
                    <i class="fas fa-store"></i> Collection
                </button>
            </div>

            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <!-- Orders Category -->
                    <div class="faq-category" data-category="orders">
                        <h3 class="category-title">
                            <i class="fas fa-shopping-cart me-2"></i>Orders & Tracking
                        </h3>

                        <div class="accordion" id="ordersAccordion">
                            <div class="accordion-item faq-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#order1">
                                        How do I place an order?
                                    </button>
                                </h2>
                                <div id="order1" class="accordion-collapse collapse" data-bs-parent="#ordersAccordion">
                                    <div class="accordion-body">
                                        <p>To place an order, visit our shop at Jalan Taib 3, Pontian District, Johor. Our staff will:</p>
                                        <ul>
                                            <li>Discuss your requirements and preferences</li>
                                            <li>Take precise body measurements</li>
                                            <li>Help you select fabric and design</li>
                                            <li>Provide you with a quote and timeline</li>
                                            <li>Issue an order ID for tracking</li>
                                        </ul>
                                        <p>You can also call us at +60 12-345 6789 to schedule an appointment.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item faq-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#order2">
                                        How can I track my order?
                                    </button>
                                </h2>
                                <div id="order2" class="accordion-collapse collapse" data-bs-parent="#ordersAccordion">
                                    <div class="accordion-body">
                                        <p>You can track your order online using your Order ID:</p>
                                        <ol>
                                            <li>Visit our <a href="tracking.php">Order Tracking</a> page</li>
                                            <li>Enter your Order ID (format: TT-YYYYMMDD-XXX)</li>
                                            <li>Click "Track Order" to view real-time status</li>
                                        </ol>
                                        <p>Your Order ID was provided on your receipt when you placed the order.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item faq-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#order3">
                                        Can I modify my order after placing it?
                                    </button>
                                </h2>
                                <div id="order3" class="accordion-collapse collapse" data-bs-parent="#ordersAccordion">
                                    <div class="accordion-body">
                                        <p>Yes, modifications are possible depending on the order status:</p>
                                        <ul>
                                            <li><strong>Pending/Assigned:</strong> Full modifications possible</li>
                                            <li><strong>In Progress:</strong> Limited changes may be possible - contact us immediately</li>
                                            <li><strong>Completed:</strong> Changes require remaking - additional charges apply</li>
                                        </ul>
                                        <p>Please contact us as soon as possible at +60 12-345 6789 to discuss modifications.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item faq-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#order4">
                                        What if I lost my Order ID?
                                    </button>
                                </h2>
                                <div id="order4" class="accordion-collapse collapse" data-bs-parent="#ordersAccordion">
                                    <div class="accordion-body">
                                        <p>No worries! You can retrieve your Order ID by:</p>
                                        <ul>
                                            <li>Checking your original receipt</li>
                                            <li>Looking for confirmation email (if provided)</li>
                                            <li>Contacting us with your name and phone number</li>
                                            <li>Visiting our shop with identification</li>
                                        </ul>
                                        <p>Call us at +60 12-345 6789 and we'll help you locate your order.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Services Category -->
                    <div class="faq-category" data-category="services">
                        <h3 class="category-title">
                            <i class="fas fa-cut me-2"></i>Services & Customization
                        </h3>

                        <div class="accordion" id="servicesAccordion">
                            <div class="accordion-item faq-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#service1">
                                        What tailoring services do you offer?
                                    </button>
                                </h2>
                                <div id="service1" class="accordion-collapse collapse" data-bs-parent="#servicesAccordion">
                                    <div class="accordion-body">
                                        <p>We specialize in custom tailoring for:</p>
                                        <ul>
                                            <li><strong>Baju Melayu:</strong> Traditional Malay attire (full set)</li>
                                            <li><strong>Baju Kurung:</strong> Women's traditional outfit</li>
                                            <li><strong>Baju Kebaya:</strong> Elegant traditional dress</li>
                                            <li><strong>Baju Kurta:</strong> Comfortable and stylish attire</li>
                                            <li><strong>Clothing Repairs:</strong> Alterations and fixes</li>
                                            <li><strong>Custom Measurements:</strong> Personalized fitting</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item faq-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#service2">
                                        Do you provide fabric or do I bring my own?
                                    </button>
                                </h2>
                                <div id="service2" class="accordion-collapse collapse" data-bs-parent="#servicesAccordion">
                                    <div class="accordion-body">
                                        <p>We offer both options:</p>
                                        <ul>
                                            <li><strong>Our Fabrics:</strong> Wide selection of premium materials available in-shop</li>
                                            <li><strong>Your Fabric:</strong> You're welcome to bring your own fabric - we'll tailor it perfectly</li>
                                        </ul>
                                        <p>If you bring your own fabric, please ensure you have sufficient material. Our staff can advise on quantity needed.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item faq-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#service3">
                                        Can you match a specific design or style?
                                    </button>
                                </h2>
                                <div id="service3" class="accordion-collapse collapse" data-bs-parent="#servicesAccordion">
                                    <div class="accordion-body">
                                        <p>Absolutely! Bring in photos or samples of your desired design. Our experienced tailors can:</p>
                                        <ul>
                                            <li>Replicate existing designs</li>
                                            <li>Modify designs to your preferences</li>
                                            <li>Suggest improvements for better fit</li>
                                            <li>Create custom designs based on your ideas</li>
                                        </ul>
                                        <p>We encourage you to bring reference images for the best results.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item faq-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#service4">
                                        Do you offer fitting sessions?
                                    </button>
                                </h2>
                                <div id="service4" class="accordion-collapse collapse" data-bs-parent="#servicesAccordion">
                                    <div class="accordion-body">
                                        <p>Yes! We provide fitting sessions to ensure perfect fit:</p>
                                        <ul>
                                            <li><strong>Initial Measurement:</strong> Taken when placing order</li>
                                            <li><strong>Mid-Work Fitting:</strong> Available for complex orders</li>
                                            <li><strong>Final Fitting:</strong> Before completion to check fit</li>
                                            <li><strong>Post-Delivery Adjustments:</strong> Free minor alterations within 7 days</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Category -->
                    <div class="faq-category" data-category="pricing">
                        <h3 class="category-title">
                            <i class="fas fa-dollar-sign me-2"></i>Pricing & Payment
                        </h3>

                        <div class="accordion" id="pricingAccordion">
                            <div class="accordion-item faq-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#price1">
                                        How much does custom tailoring cost?
                                    </button>
                                </h2>
                                <div id="price1" class="accordion-collapse collapse" data-bs-parent="#pricingAccordion">
                                    <div class="accordion-body">
                                        <p>Pricing varies based on several factors:</p>
                                        <ul>
                                            <li>Type of garment</li>
                                            <li>Fabric choice (if using ours)</li>
                                            <li>Design complexity</li>
                                            <li>Special requirements or embellishments</li>
                                        </ul>
                                        <p>Visit our shop for a free quote. We'll provide transparent pricing with no hidden fees.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item faq-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#price2">
                                        What payment methods do you accept?
                                    </button>
                                </h2>
                                <div id="price2" class="accordion-collapse collapse" data-bs-parent="#pricingAccordion">
                                    <div class="accordion-body">
                                        <p>We accept the following payment methods:</p>
                                        <ul>
                                            <li>Cash</li>
                                            <li>Bank Transfer</li>
                                            <li>Online Banking</li>
                                            <li>E-Wallet (Touch 'n Go, GrabPay)</li>
                                        </ul>
                                        <p>Deposit of 50% is required when placing order, with balance due upon collection.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item faq-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#price3">
                                        Do I need to pay a deposit?
                                    </button>
                                </h2>
                                <div id="price3" class="accordion-collapse collapse" data-bs-parent="#pricingAccordion">
                                    <div class="accordion-body">
                                        <p>Yes, we require a deposit to begin work:</p>
                                        <ul>
                                            <li><strong>Standard Orders:</strong> 50% deposit</li>
                                            <li><strong>Custom Fabric Orders:</strong> 100% upfront (non-refundable)</li>
                                            <li><strong>Repairs:</strong> Full payment upon completion</li>
                                        </ul>
                                        <p>This helps us purchase materials and allocate tailor time for your order.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item faq-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#price4">
                                        What is your refund policy?
                                    </button>
                                </h2>
                                <div id="price4" class="accordion-collapse collapse" data-bs-parent="#pricingAccordion">
                                    <div class="accordion-body">
                                        <p>Our refund policy:</p>
                                        <ul>
                                            <li><strong>Before Work Starts:</strong> 90% refund (10% admin fee)</li>
                                            <li><strong>Work In Progress:</strong> No refund, but credit note issued</li>
                                            <li><strong>Completed Orders:</strong> No refund (free alterations within 7 days)</li>
                                            <li><strong>Our Error:</strong> Full refund or free remake</li>
                                        </ul>
                                        <p>Custom orders are made specifically for you, so refunds are limited.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Category -->
                    <div class="faq-category" data-category="delivery">
                        <h3 class="category-title">
                            <i class="fas fa-store me-2"></i>Collection & Pick-Up
                        </h3>

                        <div class="accordion" id="deliveryAccordion">
                            <div class="accordion-item faq-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#delivery1">
                                        How long does it take to complete an order?
                                    </button>
                                </h2>
                                <div id="delivery1" class="accordion-collapse collapse" data-bs-parent="#deliveryAccordion">
                                    <div class="accordion-body">
                                        <p>Completion time varies by order type:</p>
                                        <ul>
                                            <li><strong>Simple Repairs:</strong> 3-5 days</li>
                                            <li><strong>Baju Kurta:</strong> 7-10 days</li>
                                            <li><strong>Baju Melayu/Kurung:</strong> 10-14 days</li>
                                            <li><strong>Baju Kebaya:</strong> 14-21 days</li>
                                            <li><strong>Rush Orders:</strong> Available with 50% surcharge</li>
                                        </ul>
                                        <p>Exact timeline will be provided when you place your order.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item faq-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#delivery2">
                                        Do you offer delivery services?
                                    </button>
                                </h2>
                                <div id="delivery2" class="accordion-collapse collapse" data-bs-parent="#deliveryAccordion">
                                    <div class="accordion-body">
                                        <p><strong>No, we do not offer delivery services.</strong></p>
                                        <p>All completed orders must be collected in person from our shop at:</p>
                                        <div class="alert alert-info mt-3 mb-3">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            <strong>WARISAN EWAN NIAGA RESOURCES</strong><br>
                                            Jalan Taib 3, Pontian District, Johor
                                        </div>
                                        <p>We'll contact you when your order is ready for collection. Please bring your order receipt or Order ID when collecting.</p>
                                        <p class="mb-0"><strong>Note:</strong> Photo identification may be required for collection.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item faq-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#delivery3">
                                        What are your shop opening hours?
                                    </button>
                                </h2>
                                <div id="delivery3" class="accordion-collapse collapse" data-bs-parent="#deliveryAccordion">
                                    <div class="accordion-body">
                                        <p>Our shop is open:</p>
                                        <ul>
                                            <li><strong>Monday - Saturday:</strong> 9:00 AM - 6:00 PM</li>
                                            <li><strong>Sunday:</strong> 10:00 AM - 4:00 PM</li>
                                            <li><strong>Public Holidays:</strong> Closed (check our notice)</li>
                                        </ul>
                                        <p>We recommend calling ahead during festive seasons to confirm hours.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item faq-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#delivery4">
                                        What if I can't collect my order on time?
                                    </button>
                                </h2>
                                <div id="delivery4" class="accordion-collapse collapse" data-bs-parent="#deliveryAccordion">
                                    <div class="accordion-body">
                                        <p>No problem! Here's what happens:</p>
                                        <ul>
                                            <li>We'll hold your order for <strong>30 days</strong> free of charge</li>
                                            <li>After 30 days, <strong>RM 5/day</strong> storage fee applies</li>
                                            <li>Please contact us if you need extended storage</li>
                                            <li>Orders uncollected after 90 days may be disposed</li>
                                        </ul>
                                        <p>Call us at +60 12-345 6789 to make alternative arrangements.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Still Have Questions Section -->
    <section class="contact-cta-section">
        <div class="container">
            <div class="contact-cta-box">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3><i class="fas fa-headset me-3"></i>Still Have Questions?</h3>
                        <p class="mb-0">Can't find the answer you're looking for? Our friendly team is here to help!</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="index.php#contact" class="btn btn-light btn-lg">
                            <i class="fas fa-phone me-2"></i>Contact Us
                        </a>
                    </div>
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
    <script src="js/faq.js"></script>
</body>
</html>
