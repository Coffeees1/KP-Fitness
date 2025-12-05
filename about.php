<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo SITE_NAME; ?></title>
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
            padding: 8rem 0 4rem;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), 
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><defs><linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:%23ff6b00;stop-opacity:0.1" /><stop offset="100%" style="stop-color:%23ff8533;stop-opacity:0.05" /></linearGradient></defs><rect width="1200" height="600" fill="url(%23grad1)"/></svg>');
            background-size: cover;
            background-position: center;
            text-align: center;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
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

        /* Content Sections */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .section {
            padding: 5rem 0;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 3rem;
            color: #ff6b00;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: #cccccc;
            margin-bottom: 3rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        /* About Content */
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            margin-bottom: 4rem;
        }

        .about-text h3 {
            font-size: 1.8rem;
            color: #ff6b00;
            margin-bottom: 1.5rem;
        }

        .about-text p {
            color: #cccccc;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .about-image {
            background: linear-gradient(135deg, #ff6b00, #ff8533);
            height: 400px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
        }

        /* Features Grid */
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

        /* Mission & Vision */
        .mission-vision {
            background: rgba(45, 45, 45, 0.5);
            border-radius: 12px;
            padding: 3rem;
            margin-bottom: 4rem;
        }

        .mission-vision h3 {
            font-size: 1.8rem;
            color: #ff6b00;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .mission-vision p {
            color: #cccccc;
            font-size: 1.1rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        /* Values */
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .value-card {
            background: rgba(26, 26, 26, 0.8);
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            border: 2px solid rgba(255, 107, 0, 0.2);
        }

        .value-card h4 {
            font-size: 1.3rem;
            color: #ff6b00;
            margin-bottom: 1rem;
        }

        .value-card p {
            color: #cccccc;
        }

        /* Team Section */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .team-card {
            background: rgba(45, 45, 45, 0.8);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            border: 1px solid rgba(255, 107, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .team-card:hover {
            transform: translateY(-5px);
        }

        .team-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff6b00, #ff8533);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 2rem;
            font-weight: 800;
            margin: 0 auto 1rem;
        }

        .team-card h4 {
            font-size: 1.3rem;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .team-card p {
            color: #ff6b00;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .team-card .bio {
            color: #cccccc;
            font-size: 0.9rem;
        }

        /* CTA Section */
        .cta-section {
            background: rgba(45, 45, 45, 0.8);
            border-radius: 12px;
            padding: 4rem 2rem;
            text-align: center;
            border: 1px solid rgba(255, 107, 0, 0.2);
        }

        .cta-section h3 {
            font-size: 2rem;
            color: #ff6b00;
            margin-bottom: 1rem;
        }

        .cta-section p {
            color: #cccccc;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
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

        /* Responsive */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .about-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">KP FITNESS</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="login.php">Classes</a></li>
                <li><a href="about.php#pricing">Pricing</a></li>
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
        <div class="hero-content fade-in-up">
            <h1>About KP Fitness</h1>
            <p>Empowering lives through fitness, technology, and community since 2020.</p>
        </div>
    </section>

    <!-- About Content -->
    <section class="section">
        <div class="container">
            <div class="about-content fade-in-up">
                <div class="about-text">
                    <h3>Our Story</h3>
                    <p>KP Fitness was founded in 2020 with a simple yet powerful mission: to make fitness accessible, enjoyable, and effective for everyone. What started as a small local gym has evolved into a comprehensive fitness ecosystem that combines cutting-edge technology with expert training.</p>
                    <p>We believe that fitness is not just about physical transformation, but about building confidence, discipline, and a supportive community. Our state-of-the-art facility spans over 9,100 square feet, featuring specialized zones for various fitness needs and an official MaxPump experience center.</p>
                    <p>Through our innovative digital platform, we've revolutionized how our members interact with fitness services, making booking, tracking, and achieving fitness goals more convenient than ever before.</p>
                </div>
                <div class="about-image">
                    <div>
                        <i class="fas fa-dumbbell" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                        <p>Your Fitness Journey Starts Here</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="section" style="background: rgba(26, 26, 26, 0.5);">
        <div class="container">
            <div class="mission-vision fade-in-up">
                <h3>Our Mission</h3>
                <p>To empower individuals to unlock their inner strength and achieve holistic well-being through innovative fitness solutions, expert guidance, and a supportive community environment.</p>
                
                <h3>Our Vision</h3>
                <p>To become the leading fitness destination that seamlessly integrates technology, expertise, and community to create transformative fitness experiences for people of all fitness levels.</p>
            </div>
        </div>
    </section>

    <!-- Core Values -->
    <section class="section">
        <div class="container">
            <h2 class="section-title fade-in-up">Our Core Values</h2>
            <div class="values-grid fade-in-up">
                <div class="value-card">
                    <h4><i class="fas fa-heart"></i> Passion</h4>
                    <p>We're passionate about fitness and dedicated to helping our members achieve their goals with enthusiasm and energy.</p>
                </div>
                <div class="value-card">
                    <h4><i class="fas fa-lightbulb"></i> Innovation</h4>
                    <p>We embrace technology and innovation to provide cutting-edge fitness solutions and enhance the member experience.</p>
                </div>
                <div class="value-card">
                    <h4><i class="fas fa-users"></i> Community</h4>
                    <p>We foster a supportive and inclusive community where everyone feels welcome and motivated to succeed.</p>
                </div>
                <div class="value-card">
                    <h4><i class="fas fa-medal"></i> Excellence</h4>
                    <p>We strive for excellence in everything we do, from our facilities to our training programs and customer service.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="section" style="background: rgba(26, 26, 26, 0.5);">
        <div class="container">
            <h2 class="section-title fade-in-up">Why Choose KP Fitness?</h2>
            <p class="section-subtitle fade-in-up">We offer a comprehensive fitness experience that goes beyond traditional gym services.</p>
            <div class="features-grid fade-in-up">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Smart Booking System</h3>
                    <p>Our advanced reservation system allows you to book classes in real-time, manage your schedule, and never miss a session.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3>AI Workout Planner</h3>
                    <p>Get personalized workout plans generated by our AI system based on your fitness level, goals, and body metrics.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3>Expert Trainers</h3>
                    <p>Learn from certified fitness professionals who provide personalized guidance and support throughout your journey.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile Access</h3>
                    <p>Access your account, book classes, and track progress from any device with our responsive web platform.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Progress Tracking</h3>
                    <p>Monitor your fitness journey with detailed analytics, progress reports, and achievement milestones.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3>Flexible Payments</h3>
                    <p>Choose from multiple payment options including Touch & Go, credit cards, and more with secure processing.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="section">
        <div class="container">
            <h2 class="section-title fade-in-up">Meet Our Expert Team</h2>
            <div class="team-grid fade-in-up">
                <div class="team-card">
                    <div class="team-avatar">JD</div>
                    <h4>John Doe</h4>
                    <p>Head Trainer</p>
                    <div class="bio">Certified fitness professional with 10+ years of experience in strength training and nutrition coaching.</div>
                </div>
                <div class="team-card">
                    <div class="team-avatar">SM</div>
                    <h4>Sarah Miller</h4>
                    <p>Yoga Specialist</p>
                    <div class="bio">Experienced yoga instructor specializing in mindfulness, flexibility, and stress reduction techniques.</div>
                </div>
                <div class="team-card">
                    <div class="team-avatar">MJ</div>
                    <h4>Mike Johnson</h4>
                    <p>HIIT Expert</p>
                    <div class="bio">High-intensity training specialist focused on weight loss, endurance, and athletic performance.</div>
                </div>
                <div class="team-card">
                    <div class="team-avatar">AL</div>
                    <h4>Amy Lee</h4>
                    <p>Pilates Instructor</p>
                    <div class="bio">Certified Pilates instructor with expertise in core strength, posture improvement, and rehabilitation.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Membership Pricing -->
    <section class="section" id="pricing" style="background: rgba(26, 26, 26, 0.5);">
        <div class="container">
            <h2 class="section-title fade-in-up">Membership Plans</h2>
            <p class="section-subtitle fade-in-up">Choose the perfect plan for your fitness journey</p>
            <div class="features-grid fade-in-up">
                <div class="feature-card">
                    <h3>One-Time</h3>
                    <div style="font-size: 2rem; font-weight: 800; color: #ff6b00; margin: 1rem 0;">RM 25-35</div>
                    <p>Per class</p>
                    <ul style="list-style: none; margin: 2rem 0; text-align: left;">
                        <li style="padding: 0.5rem 0; color: #cccccc;"><i class="fas fa-check" style="color: #ff6b00; margin-right: 0.5rem;"></i> Single class access</li>
                        <li style="padding: 0.5rem 0; color: #cccccc;"><i class="fas fa-check" style="color: #ff6b00; margin-right: 0.5rem;"></i> No commitment</li>
                        <li style="padding: 0.5rem 0; color: #cccccc;"><i class="fas fa-check" style="color: #ff6b00; margin-right: 0.5rem;"></i> Pay as you go</li>
                        <li style="padding: 0.5rem 0; color: #cccccc;"><i class="fas fa-check" style="color: #ff6b00; margin-right: 0.5rem;"></i> No discount</li>
                    </ul>
                    <a href="register.php" class="btn btn-secondary">Get Started</a>
                </div>
                <div class="feature-card" style="border-color: #ff6b00; transform: scale(1.05);">
                    <div style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: #ff6b00; color: #ffffff; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600;">Most Popular</div>
                    <h3>Monthly</h3>
                    <div style="font-size: 2rem; font-weight: 800; color: #ff6b00; margin: 1rem 0;">RM 118</div>
                    <p>Per month</p>
                    <ul style="list-style: none; margin: 2rem 0; text-align: left;">
                        <li style="padding: 0.5rem 0; color: #cccccc;"><i class="fas fa-check" style="color: #ff6b00; margin-right: 0.5rem;"></i> Unlimited classes</li>
                        <li style="padding: 0.5rem 0; color: #cccccc;"><i class="fas fa-check" style="color: #ff6b00; margin-right: 0.5rem;"></i> Access to all trainers</li>
                        <li style="padding: 0.5rem 0; color: #cccccc;"><i class="fas fa-check" style="color: #ff6b00; margin-right: 0.5rem;"></i> Priority booking</li>
                        <li style="padding: 0.5rem 0; color: #cccccc;"><i class="fas fa-check" style="color: #ff6b00; margin-right: 0.5rem;"></i> No setup fees</li>
                    </ul>
                    <a href="register.php" class="btn btn-primary">Choose Plan</a>
                </div>
                <div class="feature-card">
                    <h3>Yearly</h3>
                    <div style="font-size: 2rem; font-weight: 800; color: #ff6b00; margin: 1rem 0;">RM 1,183</div>
                    <p>Per year</p>
                    <ul style="list-style: none; margin: 2rem 0; text-align: left;">
                        <li style="padding: 0.5rem 0; color: #cccccc;"><i class="fas fa-check" style="color: #ff6b00; margin-right: 0.5rem;"></i> All monthly benefits</li>
                        <li style="padding: 0.5rem 0; color: #cccccc;"><i class="fas fa-check" style="color: #ff6b00; margin-right: 0.5rem;"></i> 2 months free</li>
                        <li style="padding: 0.5rem 0; color: #cccccc;"><i class="fas fa-check" style="color: #ff6b00; margin-right: 0.5rem;"></i> Guest passes (up to 2)</li>
                        <li style="padding: 0.5rem 0; color: #cccccc;"><i class="fas fa-check" style="color: #ff6b00; margin-right: 0.5rem;"></i> Exclusive events</li>
                    </ul>
                    <a href="register.php" class="btn btn-secondary">Save More</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="section" id="contact">
        <div class="container">
            <h2 class="section-title fade-in-up">Get In Touch</h2>
            <div class="cta-section fade-in-up">
                <h3>Ready to Start Your Fitness Journey?</h3>
                <p>Join thousands of members who have transformed their lives with KP Fitness. Our team is here to support you every step of the way.</p>
                <div class="cta-buttons">
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        Start Free Trial
                    </a>
                    <a href="login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i>
                        Member Login
                    </a>
                </div>
                <div style="margin-top: 2rem; color: #cccccc;">
                    <p><i class="fas fa-phone" style="color: #ff6b00; margin-right: 0.5rem;"></i> +60 12-345 6789</p>
                    <p><i class="fas fa-envelope" style="color: #ff6b00; margin-right: 0.5rem;"></i> info@kpfitness.com</p>
                    <p><i class="fas fa-map-marker-alt" style="color: #ff6b00; margin-right: 0.5rem;"></i> 123 Fitness Street, Kuala Lumpur</p>
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

        document.querySelectorAll('.feature-card, .team-card, .value-card').forEach(el => {
            observer.observe(el);
        });

        // Navbar background on scroll
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                navbar.style.background = 'rgba(26, 26, 26, 0.98)';
            } else {
                navbar.style.background = 'rgba(26, 26, 26, 0.95)';
            }
        });
    </script>
</body>
</html>