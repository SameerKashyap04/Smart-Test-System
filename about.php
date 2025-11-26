<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Online Exam Platform</title>
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .header {
            background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-links {
            display: flex;
            gap: 1rem;
        }
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
        }
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        .content-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        .mission-vision {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .mission-card, .vision-card {
            flex: 1;
            min-width: 300px;
            padding: 2rem;
            border-radius: 15px;
            background: #f8f9fa;
            text-align: center;
        }
        .mission-card h3, .vision-card h3 {
            color: #4e54c8;
            margin-bottom: 1rem;
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .feature-item {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            text-align: center;
        }
        .feature-icon {
            font-size: 2rem;
            color: #4e54c8;
            margin-bottom: 1rem;
        }
        .feature-item h4 {
            margin-bottom: 0.5rem;
            color: #333;
        }
        .feature-item p {
            color: #6c757d;
            margin: 0;
        }
        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-top: 2rem;
        }
        .stat-item {
            flex: 1;
            min-width: 200px;
            text-align: center;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #4e54c8;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #6c757d;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="header">
            <h1><i class="fas fa-graduation-cap me-2"></i>Online Exam Platform</h1>
            <div class="nav-links">
                <a href="index.php" class="nav-link"><i class="fas fa-home me-1"></i> Home</a>
                <a href="about.php" class="nav-link active"><i class="fas fa-info-circle me-1"></i> About Us</a>
                <a href="team.php" class="nav-link"><i class="fas fa-users me-1"></i> Team</a>
                <a href="contact.php" class="nav-link"><i class="fas fa-envelope me-1"></i> Contact</a>
            </div>
        </div>
        
        <div class="content-card">
            <h2 class="mb-4">About Our Platform</h2>
            <p class="mb-4">Welcome to the Online Exam Platform, a comprehensive solution designed to revolutionize the way examinations are conducted in educational institutions. Our platform bridges the gap between traditional examination methods and modern technology, providing a seamless experience for both educators and students.</p>
            
            <div class="mission-vision">
                <div class="mission-card">
                    <div class="icon-circle">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>Our Mission</h3>
                    <p>To empower educational institutions with cutting-edge technology that enhances the examination process, making it more efficient, secure, and accessible for all stakeholders.</p>
                </div>
                
                <div class="vision-card">
                    <div class="icon-circle">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>Our Vision</h3>
                    <p>To become the leading online examination platform globally, setting new standards for digital assessment and contributing to the advancement of education technology.</p>
                </div>
            </div>
            
            <h3 class="mb-3">Key Features</h3>
            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>Secure Testing</h4>
                    <p>Advanced security measures to prevent cheating and ensure exam integrity</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4>Time Management</h4>
                    <p>Efficient time tracking and management for both students and examiners</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h4>Instant Results</h4>
                    <p>Automated grading and immediate result generation for quick feedback</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h4>Mobile Friendly</h4>
                    <p>Responsive design that works seamlessly across all devices</p>
                </div>
            </div>
            
            <h3 class="mb-3">Our Impact</h3>
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number">10,000+</div>
                    <div class="stat-label">Exams Conducted</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-number">50,000+</div>
                    <div class="stat-label">Students Served</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-number">100+</div>
                    <div class="stat-label">Educational Institutions</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-number">99.9%</div>
                    <div class="stat-label">Uptime</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 