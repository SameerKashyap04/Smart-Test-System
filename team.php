<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Team - Online Exam Platform</title>
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
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .team-member {
            background: #f8f9fa;
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .team-member:hover {
            transform: translateY(-5px);
        }
        .member-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        .member-info {
            padding: 1.5rem;
            text-align: center;
        }
        .member-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
        }
        .member-bio {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .social-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        .social-link {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #4e54c8;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .social-link:hover {
            background: #8f94fb;
            color: white;
            transform: scale(1.1);
        }
        .team-section {
            margin-bottom: 3rem;
        }
        .section-title {
            color: #4e54c8;
            margin-bottom: 1.5rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="header">
            <h1><i class="fas fa-graduation-cap me-2"></i>Online Exam Platform</h1>
            <div class="nav-links">
                <a href="index.php" class="nav-link"><i class="fas fa-home me-1"></i> Home</a>
                <a href="about.php" class="nav-link"><i class="fas fa-info-circle me-1"></i> About Us</a>
                <a href="team.php" class="nav-link active"><i class="fas fa-users me-1"></i> Team</a>
                <a href="contact.php" class="nav-link"><i class="fas fa-envelope me-1"></i> Contact</a>
            </div>
        </div>
        
        <div class="content-card">
            <h2 class="text-center mb-4">Meet Our Team</h2>
            <p class="text-center mb-5">Our dedicated team of professionals works tirelessly to provide the best online examination experience for educational institutions worldwide.</p>
            
            <div class="team-section">
                <h3 class="section-title">Leadership Team</h3>
                <div class="team-grid">
                    <div class="team-member">
                        <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a" alt="John Doe" class="member-image">
                        <div class="member-info">
                            <h4 class="member-name">John Doe</h4>
                            <p class="member-bio">With over 15 years of experience in edtech, John leads our vision to revolutionize online examinations.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="team-member">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2" alt="Jane Smith" class="member-image">
                        <div class="member-info">
                            <h4 class="member-name">Jane Smith</h4>
                            <p class="member-bio">Jane oversees all technical aspects of our platform, ensuring reliability and innovation.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="team-section">
                <h3 class="section-title">Development Team</h3>
                <div class="team-grid">
                    <div class="team-member">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d" alt="Mike Johnson" class="member-image">
                        <div class="member-info">
                            <h4 class="member-name">Mike Johnson</h4>
                            <p class="member-bio">Mike leads our development team, focusing on creating robust and scalable solutions.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="team-member">
                        <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330" alt="Sarah Wilson" class="member-image">
                        <div class="member-info">
                            <h4 class="member-name">Sarah Wilson</h4>
                            <p class="member-bio">Sarah ensures our platform is not only functional but also beautiful and user-friendly.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-dribbble"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="team-member">
                        <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e" alt="Abidit Gogoi" class="member-image">
                        <div class="member-info">
                            <h4 class="member-name">Abidit Gogoi</h4>
                            <p class="member-bio">Abidit specializes in database architecture and server-side development for our platform.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="team-member">
                        <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e" alt="Santonu Nath" class="member-image">
                        <div class="member-info">
                            <h4 class="member-name">Santonu Nath</h4>
                            <p class="member-bio">Santonu creates responsive and intuitive user interfaces for our examination platform.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="team-member">
                        <img src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d" alt="Amanjit Singh" class="member-image">
                        <div class="member-info">
                            <h4 class="member-name">Amanjit Singh</h4>
                            <p class="member-bio">Amanjit works on both frontend and backend development, ensuring seamless integration.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="team-section">
                <h3 class="section-title">Support Team</h3>
                <div class="team-grid">
                    <div class="team-member">
                        <img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7" alt="David Brown" class="member-image">
                        <div class="member-info">
                            <h4 class="member-name">David Brown</h4>
                            <p class="member-bio">David ensures our clients receive exceptional support and assistance.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="team-member">
                        <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80" alt="Emily Davis" class="member-image">
                        <div class="member-info">
                            <h4 class="member-name">Emily Davis</h4>
                            <p class="member-bio">Emily provides technical assistance and troubleshooting support to our users.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="team-member">
                        <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2" alt="Preeti Saikia" class="member-image">
                        <div class="member-info">
                            <h4 class="member-name">Preeti Saikia</h4>
                            <p class="member-bio">Preeti handles user inquiries and provides personalized assistance to our clients.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="team-member">
                        <img src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d" alt="Bishal Saikia" class="member-image">
                        <div class="member-info">
                            <h4 class="member-name">Bishal Saikia</h4>
                            <p class="member-bio">Bishal specializes in resolving complex technical issues and providing training to users.</p>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 