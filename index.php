<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TailorTrack - Custom Clothing Order Tracking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/home.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-tshirt me-2"></i>
                <span>TailorTrack</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tracking.php">Track Order</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="faq.php">FAQ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-outline-light" href="login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Slideshow -->
    <section id="home" class="hero-section">
        <div class="hero-slideshow">
            <div class="slide active" style="background-image: url('images/hero/tailor-shop-1.jpg');">
                <div class="slide-overlay"></div>
            </div>
            <div class="slide" style="background-image: url('images/hero/tailor-shop-2.jpg');">
                <div class="slide-overlay"></div>
            </div>
            <div class="slide" style="background-image: url('images/hero/tailor-shop-3.jpg');">
                <div class="slide-overlay"></div>
            </div>
        </div>

        <div class="container hero-content">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-7">
                    <div class="hero-text-box">
                        <h1 class="hero-title animate-fade-in">Custom Tailoring Excellence</h1>
                        <p class="hero-subtitle animate-fade-in-delay">Experience the art of bespoke tailoring. From traditional Baju Melayu to modern designs, we craft garments that fit your style perfectly.</p>
                        <div class="hero-buttons animate-fade-in-delay-2">
                            <a href="#tracking" class="btn btn-primary btn-lg me-3">
                                <i class="fas fa-search me-2"></i>Track Your Order
                            </a>
                            <a href="#services" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-cut me-2"></i>Our Services
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slideshow Controls -->
        <div class="slideshow-controls">
            <button class="slide-btn prev" onclick="changeSlide(-1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="slide-btn next" onclick="changeSlide(1)">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>

        <!-- Slideshow Indicators -->
        <div class="slideshow-indicators">
            <span class="indicator active" onclick="currentSlide(0)"></span>
            <span class="indicator" onclick="currentSlide(1)"></span>
            <span class="indicator" onclick="currentSlide(2)"></span>
        </div>

        <div class="scroll-indicator">
            <a href="#about"><i class="fas fa-chevron-down"></i></a>
        </div>
    </section>

    <!-- Promotional Banner -->
    <section class="promo-banner">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3><i class="fas fa-star me-2"></i>Special Offer: Get 15% Off Your First Custom Order!</h3>
                    <p class="mb-0">Book your appointment this month and enjoy exclusive discounts on all tailoring services.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="#contact" class="btn btn-light btn-lg">
                        <i class="fas fa-phone me-2"></i>Book Now
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <h2 class="section-title">About WARISAN EWAN NIAGA RESOURCES</h2>
                    <p class="section-text">
                        With years of experience in the tailoring industry, WARISAN EWAN NIAGA RESOURCES has established itself as a trusted name for custom clothing in Pontian, Johor. Our skilled tailors combine traditional craftsmanship with modern techniques to deliver garments that fit perfectly and reflect your personal style.
                    </p>
                    <div class="about-features">
                        <div class="feature-item">
                            <i class="fas fa-award"></i>
                            <div>
                                <h5>Quality Craftsmanship</h5>
                                <p>Every stitch matters in our pursuit of perfection.</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h5>Timely Delivery</h5>
                                <p>We respect your time with reliable completion dates.</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-users"></i>
                            <div>
                                <h5>Customer Focused</h5>
                                <p>Your satisfaction is our ultimate priority.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-image-grid">
                        <div class="about-img-main">
                            <img src="images/about/tailor-working.jpg" alt="Tailor at work" class="img-fluid rounded shadow">
                            <div class="experience-badge">
                                <div class="badge-content">
                                    <h3>15+</h3>
                                    <p>Years Experience</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Showcase Section -->
    <section class="gallery-section section-padding bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="section-title d-inline-block">Our Craftsmanship</h2>
                    <p class="section-subtitle">A glimpse of our finest custom tailoring work</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="gallery-item">
                        <img src="images/gallery/baju-melayu.jpg" alt="Baju Melayu" class="img-fluid">
                        <div class="gallery-overlay">
                            <div class="gallery-text">
                                <h5>Baju Melayu</h5>
                                <p>Traditional elegance</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="gallery-item">
                        <img src="images/gallery/baju-kurung.jpg" alt="Baju Kurung" class="img-fluid">
                        <div class="gallery-overlay">
                            <div class="gallery-text">
                                <h5>Baju Kurung</h5>
                                <p>Graceful design</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="gallery-item">
                        <img src="images/gallery/kebaya.jpg" alt="Kebaya" class="img-fluid">
                        <div class="gallery-overlay">
                            <div class="gallery-text">
                                <h5>Kebaya</h5>
                                <p>Exquisite craftsmanship</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="gallery-item">
                        <img src="images/gallery/tailor-process.jpg" alt="Tailoring Process" class="img-fluid">
                        <div class="gallery-overlay">
                            <div class="gallery-text">
                                <h5>Our Process</h5>
                                <p>Precision in every detail</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="gallery-item">
                        <img src="images/gallery/fabrics.jpg" alt="Premium Fabrics" class="img-fluid">
                        <div class="gallery-overlay">
                            <div class="gallery-text">
                                <h5>Premium Fabrics</h5>
                                <p>Quality materials</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="section-padding">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="section-title d-inline-block">Our Services</h2>
                    <p class="section-subtitle">We offer a wide range of custom clothing and repair services</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <div class="service-image">
                            <img src="images/services/baju-melayu.jpg" alt="Baju Melayu" class="img-fluid">
                        </div>
                        <div class="service-content">
                            <div class="service-icon">
                                <i class="fas fa-vest"></i>
                            </div>
                            <h5>Set Baju Melayu</h5>
                            <p>Traditional Malay attire tailored to perfection with precise measurements for both upper and lower body.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <div class="service-image">
                            <img src="images/services/baju-kurung.jpg" alt="Baju Kurung" class="img-fluid">
                        </div>
                        <div class="service-content">
                            <div class="service-icon">
                                <i class="fas fa-female"></i>
                            </div>
                            <h5>Set Baju Kurung</h5>
                            <p>Elegant traditional outfit for women with careful attention to detail and comfort.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <div class="service-image">
                            <img src="images/services/kebaya.jpg" alt="Kebaya" class="img-fluid">
                        </div>
                        <div class="service-content">
                            <div class="service-icon">
                                <i class="fas fa-gem"></i>
                            </div>
                            <h5>Set Baju Kebaya</h5>
                            <p>Exquisite kebaya sets that highlight traditional beauty with modern fitting techniques.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <div class="service-image">
                            <img src="images/services/kurta.jpg" alt="Kurta" class="img-fluid">
                        </div>
                        <div class="service-content">
                            <div class="service-icon">
                                <i class="fas fa-tshirt"></i>
                            </div>
                            <h5>Baju Kurta</h5>
                            <p>Comfortable and stylish kurta with upper body measurements for the perfect fit.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <div class="service-image">
                            <img src="images/services/repair.jpg" alt="Clothing Repair" class="img-fluid">
                        </div>
                        <div class="service-content">
                            <div class="service-icon">
                                <i class="fas fa-tools"></i>
                            </div>
                            <h5>Clothing Repair</h5>
                            <p>Professional repair services for shirts, pants, and other garments to extend their life.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <div class="service-image">
                            <img src="images/services/measurements.jpg" alt="Custom Measurements" class="img-fluid">
                        </div>
                        <div class="service-content">
                            <div class="service-icon">
                                <i class="fas fa-ruler-combined"></i>
                            </div>
                            <h5>Custom Measurements</h5>
                            <p>Precise body measurements taken by our experts to ensure perfect fitting garments.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

   <!-- Order Tracking Section -->
<section id="tracking" class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="tracking-card">
                    <h2 class="section-title text-center mb-4">Track Your Order</h2>
                    <p class="text-center mb-4">Enter your order ID to check the current status of your custom clothing</p>
                        <form id="tracking-form">
                        <div class="mb-3">
                            <label for="orderId" class="form-label">Order ID <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="orderId"
                                       placeholder="Enter order ID (e.g., TT-20240315-001)"
                                       required>
                            </div>
                            <div class="form-text">Format: TT-YYYYMMDD-XXX (e.g., TT-20240315-001)</div>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-search me-2"></i> Track Order
                            </button>
                        </div>
                    </form>
                    
                    <div id="tracking-result" class="mt-4" style="display: none;">
                        <div class="tracking-status">
                            <!-- Results will be loaded here by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- Contact Section -->
    <section id="contact" class="section-padding bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <h2 class="section-title">Contact Us</h2>
                    <p class="section-text">Visit our shop or get in touch with us for your custom clothing needs</p>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h5>Address</h5>
                                <p>Jalan Taib 3, Pontian District, Johor</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <h5>Phone</h5>
                                <p>+60 12-345 6789</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h5>Email</h5>
                                <p>info@warisanewan.com</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h5>Business Hours</h5>
                                <p>Monday - Saturday: 9:00 AM - 6:00 PM</p>
                                <p>Sunday: 10:00 AM - 4:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="contact-form-card">
                        <h4 class="mb-4">Send us a Message</h4>
                        <div id="contactFormAlert"></div>
                        <form id="contactForm">
                            <div class="mb-3">
                                <label for="contactName" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="contactName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="contactEmail" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="contactEmail" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="contactMessage" class="form-label">Message</label>
                                <textarea class="form-control" id="contactMessage" name="message" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" id="contactSubmitBtn">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        </form>
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
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="tracking.php">Track Order</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h5>Services</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Set Baju Melayu</a></li>
                        <li><a href="#">Set Baju Kurung</a></li>
                        <li><a href="#">Set Baju Kebaya</a></li>
                        <li><a href="#">Baju Kurta</a></li>
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
                    <p>Designed with <i class="fas fa-heart text-danger"></i> for TailorTrack</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/home.js"></script>
</body>
</html>