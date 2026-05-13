<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bacolod City Health Office - Bridging Care through Digital Innovation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #667eea 100%);
            color: white;
            padding: 100px 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        .hero-content {
            text-align: center;
        }
        .slogan {
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .main-title {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            text-shadow: 3px 3px 6px rgba(0,0,0,0.4);
            letter-spacing: -1px;
            animation: fadeInUp 1s ease-out;
        }
        .subtitle {
            font-size: 1.3rem;
            margin-bottom: 3rem;
            opacity: 0.8;
        }
        .cta-button {
            font-size: 1.3rem;
            padding: 18px 50px;
            border-radius: 50px;
            background: linear-gradient(45deg, #fff 0%, #f8f9fa 100%);
            color: #1e3c72;
            border: 2px solid rgba(255,255,255,0.3);
            font-weight: 700;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 1.2s ease-out 0.3s both;
        }
        .cta-button:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 20px 45px rgba(0,0,0,0.4);
            color: #1e3c72;
            background: linear-gradient(45deg, #f8f9fa 0%, #e9ecef 100%);
            border-color: rgba(255,255,255,0.5);
        }
        .cta-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        .cta-button:hover::before {
            left: 100%;
        }
        .features-section {
            padding: 80px 0;
            background: #f8f9fa;
        }
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 20px;
        }
        .feature-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        .feature-description {
            color: #666;
            line-height: 1.6;
        }
        .info-section {
            padding: 60px 0;
            background: white;
        }
        .info-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
        }
        .info-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .info-text {
            font-size: 1.1rem;
            line-height: 1.6;
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
            }
            70% {
                box-shadow: 0 0 0 20px rgba(255, 255, 255, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
            }
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .logo-container {
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease-out;
        }
        .logo-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255,255,255,0.3);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        .logo-img:hover {
            transform: scale(1.1);
            border-color: rgba(255,255,255,0.5);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }
        .navbar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        .navbar-brand {
            color: white !important;
            font-weight: 600;
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: white !important;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-hospital"></i> Bacolod City Health Office
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#info">Information</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="personal_info.php">Book Now</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <div class="logo-container">
                    <img src="images/bcd.jpg" alt="Bacolod City Health Office Logo" class="logo-img">
                </div>
                <h1 class="main-title">Bacolod City Health Office</h1>
                <p class="slogan">Bridging Care through Digital Innovation</p>
                <p class="subtitle">Easy, Fast, and Convenient Healthcare Appointment Booking</p>
                <a href="personal_info.php" class="cta-button pulse">
                    <i class="bi bi-calendar-check"></i> Schedule Your Appointment Now
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Why Choose Our System?</h2>
                <p class="lead text-muted">Experience healthcare appointment booking made simple</p>
            </div>
            
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-lightning-charge"></i>
                        </div>
                        <h3 class="feature-title">Quick & Easy</h3>
                        <p class="feature-description">Book your appointment in minutes without creating an account. Simple, intuitive interface designed for everyone.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <h3 class="feature-title">24/7 Availability</h3>
                        <p class="feature-description">Schedule appointments anytime, anywhere. No need to call during office hours or wait in long queues.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h3 class="feature-title">Secure & Private</h3>
                        <p class="feature-description">Your personal information is protected with industry-standard security measures.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-phone"></i>
                        </div>
                        <h3 class="feature-title">Mobile Friendly</h3>
                        <p class="feature-description">Access our system from any device - smartphone, tablet, or desktop with responsive design.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-bell"></i>
                        </div>
                        <h3 class="feature-title">Instant Confirmation</h3>
                        <p class="feature-description">Receive immediate confirmation of your appointment booking with all details.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <h3 class="feature-title">Expert Care</h3>
                        <p class="feature-description">Connect with qualified healthcare professionals for your medical needs.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Information Section -->
    <section id="info" class="info-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold">Important Information</h2>
                <p class="lead text-muted">What you need to know before booking</p>
            </div>
            
            <div class="row">
                <div class="col-lg-6">
                    <div class="info-card">
                        <h3 class="info-title">
                            <i class="bi bi-info-circle"></i> Before You Book
                        </h3>
                        <p class="info-text">
                            • Have your personal information ready<br>
                            • Bring valid ID on appointment day<br>
                            • Arrive 15 minutes before scheduled time<br>
                            • Bring any relevant medical documents
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="info-card">
                        <h3 class="info-title">
                            <i class="bi bi-telephone"></i> Need Help?
                        </h3>
                        <p class="info-text">
                            • Call us: (034) 431-36-73<br>
                            • Email: admin_bcho@gov.ph<br>
                            • Visit: Bacolod City Health Office<br>
                            • Location: Galo BBB, Burgos Street, Barangay 20, Bacolod City<br>
                            • Hours: Monday-Friday, 8AM-5PM
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Bacolod City Health Office</h5>
                    <p>Providing quality healthcare services to the community</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; 2026 CHO Appointment System. All rights reserved.</p>
                    <p>Bridging Care through Digital Innovation</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add active state to navigation on scroll
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section');
            const navLinks = document.querySelectorAll('.nav-link');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollY >= (sectionTop - 200)) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>

