<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Unlock Your Inner Strength</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #ffffff;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 107, 0, 0.2);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: #ff6b00;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-links a:hover {
            color: #ff6b00;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: #ff6b00;
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff6b00, #ff8533);
            color: #ffffff;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 0, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: #ffffff;
            border: 2px solid #ff6b00;
        }

        .btn-secondary:hover {
            background: #ff6b00;
            color: #ffffff;
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            background: #0f0f0f;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            background: linear-gradient(rgba(40, 40, 40, 0.7), rgba(40, 40, 40, 0.7)), var(--hero-bg);
            background-size: cover;
            background-position: center;
            transition: opacity 0.9s ease;
            opacity: 1;
            z-index: 0;
        }

        .hero-bg.fade {
            opacity: 0;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #ffffff, #ff6b00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            color: #cccccc;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Features Section */
        .features {
            padding: 5rem 0;
            background: #1a1a1a;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 3rem;
            color: #ff6b00;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .feature-card {
            background: rgba(45, 45, 45, 0.8);
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(255, 107, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 107, 0, 0.2);
        }

        .feature-icon {
            font-size: 3rem;
            color: #ff6b00;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #ffffff;
        }

        .feature-card p {
            color: #cccccc;
        }

        /* Membership Plans */
        .membership {
            padding: 5rem 0;
            background: #2d2d2d;
        }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .plan-card {
            background: rgba(26, 26, 26, 0.9);
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            border: 2px solid rgba(255, 107, 0, 0.2);
            position: relative;
            transition: all 0.3s ease;
        }

        .plan-card:hover {
            border-color: #ff6b00;
            transform: translateY(-5px);
        }

        .plan-card.popular {
            border-color: #ff6b00;
            transform: scale(1.05);
        }

        .popular-badge {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #ff6b00;
            color: #ffffff;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .plan-price {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ff6b00;
            margin: 1rem 0;
        }

        .plan-features {
            list-style: none;
            margin: 2rem 0;
        }

        .plan-features li {
            padding: 0.5rem 0;
            color: #cccccc;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .plan-features li i {
            color: #ff6b00;
        }

        /* About Section */
        .about {
            padding: 5rem 0;
            background: #1a1a1a;
        }

        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .about-text h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: #ff6b00;
        }

        .about-text p {
            color: #cccccc;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .about-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-top: 2rem;
        }

        .stat-item {
            text-align: center;
            padding: 1.5rem;
            background: rgba(45, 45, 45, 0.5);
            border-radius: 8px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: #ff6b00;
            display: block;
        }

        .stat-label {
            color: #cccccc;
            font-size: 0.9rem;
        }

        /* Footer */
        .footer {
            background: #0d0d0d;
            padding: 2rem 0;
            text-align: center;
            border-top: 1px solid rgba(255, 107, 0, 0.2);
        }

        .footer p {
            color: #888888;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .about-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .about-stats {
                grid-template-columns: 1fr;
            }

            .auth-buttons {
                gap: 0.5rem;
            }

            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }

        /* Animations */
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

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 107, 0, 0.3);
            border-radius: 50%;
            border-top-color: #ff6b00;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">KP FITNESS</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="login.php">Classes</a></li>
                <li><a href="about.php#pricing">Membership</a></li>
                <li><a href="about.php#contact">Contact</a></li>
            </ul>
            <div class="auth-buttons">
                <a href="login.php" class="btn btn-secondary">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </a>
                <a href="register.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Sign Up
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-content fade-in-up">
            <h1>One Day or Day One</h1>
            <p>Start Your Journey at KP Fitness and Transform Your Body with Our Mr.Olympia Approved Equipments, Certified Expert Trainers, and State-Of-The-Art Facilities.</p>
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-primary">
                    <i class="fas fa-rocket"></i>
                    Start Your Journey
                </a>
                <a href="about.php" class="btn btn-secondary">
                    <i class="fas fa-info-circle"></i>
                    Learn More
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Why Choose KP Fitness?</h2>
            <div class="features-grid">
                <div class="feature-card fade-in-up">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Easy Booking</h3>
                    <p>Book your favorite classes instantly with our real-time reservation system. No more conflicts or double bookings.</p>
                </div>
                <div class="feature-card fade-in-up">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Expert Trainers</h3>
                    <p>Learn from certified fitness professionals who will guide you through every step of your fitness journey.</p>
                </div>
                <div class="feature-card fade-in-up">
                    <div class="feature-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h3>AI Workout Planner</h3>
                    <p>Get personalized workout plans based on your fitness level, goals, and body metrics using our AI system.</p>
                </div>
                <div class="feature-card fade-in-up">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Track Progress</h3>
                    <p>Monitor your fitness journey with detailed analytics and progress tracking features.</p>
                </div>
                <div class="feature-card fade-in-up">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile Friendly</h3>
                    <p>Access your account, book classes, and track workouts from any device, anywhere, anytime.</p>
                </div>
                <div class="feature-card fade-in-up">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Secure Payments</h3>
                    <p>Multiple payment options including Touch & Go, credit cards, and more with secure processing.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Membership Plans -->
    <section class="membership" id="pricing">
        <div class="container">
            <h2 class="section-title">Membership Plans</h2>
            <div class="plans-grid">
                <div class="plan-card">
                    <h3>One-Time</h3>
                    <div class="plan-price">RM 25-35</div>
                    <p>Per class</p>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Single class access</li>
                        <li><i class="fas fa-check"></i> No commitment</li>
                        <li><i class="fas fa-check"></i> Pay as you go</li>
                        <li><i class="fas fa-check"></i> No discount</li>
                    </ul>
                    <a href="register.php" class="btn btn-secondary">Get Started</a>
                </div>
                <div class="plan-card popular">
                    <div class="popular-badge">Most Popular</div>
                    <h3>Monthly</h3>
                    <div class="plan-price">RM 118</div>
                    <p>Per month</p>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Unlimited classes</li>
                        <li><i class="fas fa-check"></i> Access to all trainers</li>
                        <li><i class="fas fa-check"></i> Priority booking</li>
                        <li><i class="fas fa-check"></i> No setup fees</li>
                    </ul>
                    <a href="register.php" class="btn btn-primary">Choose Plan</a>
                </div>
                <div class="plan-card">
                    <h3>Yearly</h3>
                    <div class="plan-price">RM 1,183</div>
                    <p>Per year</p>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> All monthly benefits</li>
                        <li><i class="fas fa-check"></i> 2 months free</li>
                        <li><i class="fas fa-check"></i> Guest passes (up to 2)</li>
                        <li><i class="fas fa-check"></i> Exclusive events</li>
                    </ul>
                    <a href="register.php" class="btn btn-secondary">Save More</a>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>About KP Fitness</h2>
                    <p>KP Fitness is dedicated to unlocking your inner strength, promoting holistic well-being, and fostering a vibrant community of fitness enthusiasts.</p>
                    <p>Our state-of-the-art facility features specialized zones for various fitness needs, expert trainers, and a comprehensive digital platform that makes fitness accessible and enjoyable for everyone.</p>
                    <p>Whether you're a beginner or an advanced fitness enthusiast, we have the tools, classes, and support you need to achieve your goals.</p>
                    <div class="about-stats">
                        <div class="stat-item">
                            <span class="stat-number">500+</span>
                            <span class="stat-label">Active Members</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">20+</span>
                            <span class="stat-label">Expert Trainers</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">50+</span>
                            <span class="stat-label">Classes Weekly</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">95%</span>
                            <span class="stat-label">Satisfaction Rate</span>
                        </div>
                    </div>
                </div>
                     <div class="about-image">
                     <img src="https://pbs.twimg.com/media/DYqOMlrXcAA2D7W.jpg" alt="KP Fitness Team" style="width:100%; height:auto; border-radius:12px;">
                </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 KP Fitness. All rights reserved. | Designed with ❤️ for your fitness journey</p>
        </div>
    </footer>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add fade-in animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-card, .plan-card').forEach(el => {
            observer.observe(el);
        });

        // Navbar background on scroll
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                navbar.style.background = 'rgba(26, 26, 26, 0.53)';
            } else {
                navbar.style.background = 'rgba(26, 26, 26, 0.53)';
            }
        });

        // Hero background rotator with fade
        const hero = document.querySelector('.hero');
        const heroBg = document.querySelector('.hero-bg');
        const heroImages = [
            'https://i.ytimg.com/vi/VvcXOos4d-s/maxresdefault.jpg',
            'https://i.ytimg.com/vi/CWK3tVW4jtw/maxresdefault.jpg',
            'https://images.squarespace-cdn.com/content/v1/5632b369e4b0112829901f35/1531062533843-HD89SNYO7Q0N5MCU8U07/Little%2BMandarin%2BYoga%2BStudio%2BMelbourne%2BFlow'
        ];

        function setHeroImage(index) {
            if (!hero || !heroBg) return;
            const url = heroImages[index % heroImages.length];

            // Fade out, swap image, then fade back in
            heroBg.classList.add('fade');
            setTimeout(() => {
                hero.style.setProperty('--hero-bg', `url('${url}')`);
                heroBg.classList.remove('fade');
            }, 450); // half of transition duration for smoother crossfade
        }

        // Preload images to avoid flash
        heroImages.forEach(src => {
            const img = new Image();
            img.src = src;
        });

        let heroIndex = 0;
        setHeroImage(heroIndex);
        setInterval(() => {
            heroIndex = (heroIndex + 1) % heroImages.length;
            setHeroImage(heroIndex);
        }, 5000);
    </script>
</body>
</html>
