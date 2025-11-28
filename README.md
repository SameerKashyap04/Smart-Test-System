# Smart Test System 🎓🤖

**A robust, AI-powered online examination platform designed for secure and fair remote testing.**

![License](https://img.shields.io/badge/license-Proprietary-red)
![PHP](https://img.shields.io/badge/backend-PHP-blue)
![MySQL](https://img.shields.io/badge/database-MySQL-orange)
![TailwindCSS](https://img.shields.io/badge/styling-TailwindCSS-38bdf8)
![TensorFlow.js](https://img.shields.io/badge/AI-TensorFlow.js-yellow)

## 🚀 Overview

The Smart Test System is a comprehensive web application that enables educational institutions to conduct online exams with high integrity. It features advanced **AI-based proctoring** to detect suspicious activities in real-time, ensuring a cheat-proof environment without the need for human proctors.

## ✨ Key Features

### 🧠 AI Proctoring & Security
- **Face Detection:** Detects multiple faces or absence of the student.
- **Voice Monitoring:** Real-time audio analysis to detect speaking or background noise.
- **Tab Switch Detection:** Logs and limits window/tab switching.
- **Exam Freeze:** Automatically pauses the exam and issues warnings upon detecting violations.
- **Domain Lock:** Prevents unauthorized deployment on unapproved domains.

### 👨‍🏫 Examiner Module
- **Dashboard:** Analytics on active exams and student performance.
- **Exam Management:** Create, edit, and delete exams with specific settings (duration, marks, strictness).
- **Question Bank:** Support for various question types.
- **College & Branch Isolation:** Organize exams for specific departments or colleges.
- **Secret Key Access:** Secure exams with unique access codes.
- **Result Analysis:** View detailed reports and export results (PDF/Excel).

### 👨‍🎓 Student Module
- **Dashboard:** View available exams filtered by College/Branch.
- **Secure Exam Interface:** Full-screen mode with disabled controls (copy/paste prevention).
- **Real-time Feedback:** Visual warnings for proctoring violations.
- **Profile Management:** Manage details like Roll No, ID Proof, and Profile Photo.

## 🛠️ Tech Stack

- **Frontend:** HTML5, Tailwind CSS, JavaScript (Vanilla).
- **Backend:** PHP (Native).
- **Database:** MySQL.
- **AI/ML Libraries:** 
  - `Face-api.js` / `MediaPipe` (Face tracking).
  - Web Audio API (Voice detection).
- **Security:** Session management, Input sanitization, Domain locking.

## ⚙️ Installation & Setup

### Prerequisites
- PHP >= 8.0
- MySQL
- Apache/Nginx (XAMPP/MAMP recommended for local)

### Steps
1.  **Clone the Repository**
    ```bash
    git clone https://github.com/SameerKashyap04/Smart-Test-System.git
    cd Smart-Test-System
    ```

2.  **Database Setup**
    - Create a MySQL database (e.g., `smart_test_system`).
    - Import the `database_setup.sql` file provided in the root directory.
    - Run any additional schema update scripts if present (e.g., `add_voice_detection_settings.sql`).

3.  **Configuration (CRITICAL)**
    - The system uses a secured credentials file that is **NOT** included in the repo.
    - Rename `config/credentials.example.php` to `config/credentials.php`.
    - Edit `config/credentials.php` with your database details:
      ```php
      // LOCALHOST
      $LOCAL_CREDENTIALS = [
          'username' => 'root',
          'password' => '',
          'database' => 'smart_test_system'
      ];
      ```
    - **Note:** Without this step, the application will not start.

4.  **Run the Application**
    - Place the project folder in your server's root directory (e.g., `htdocs`).
    - Access via browser: `http://localhost/Smart_test_system`

## 🔒 Security Notice

This project includes built-in security mechanisms:
- **Credential Isolation:** Database passwords are never stored in the code repository.
- **System Lock:** Requires a valid license hash to operate.
- **Domain Restriction:** The application is hard-coded to run only on authorized domains (localhost, devify.live).

## 📄 License

**© 2025 Smart Test System. All Rights Reserved.**
Unauthorized copying, modification, distribution, or use of this source code is strictly prohibited.

