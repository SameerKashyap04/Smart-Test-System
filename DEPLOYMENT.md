# Deployment Guide for Hostinger

Follow these steps to host your Smart Test System on Hostinger.

## 1. Prepare Files
1.  Open `config/db.php` in your code editor.
2.  Change `$is_production = false;` to `$is_production = true;`.
3.  Fill in your Hostinger database credentials (see Step 2) in the "HOSTINGER Credentials" section.
4.  Compress all files in the project folder into a `.zip` file (excluding `.git`, `node_modules` if any).

## 2. Database Setup (Hostinger)
1.  Log in to your Hostinger hPanel.
2.  Go to **Databases** > **Management**.
3.  Create a new MySQL Database:
    *   **Database Name**: (e.g., `u123456789_smarttest`)
    *   **Username**: (e.g., `u123456789_admin`)
    *   **Password**: (Choose a strong password)
4.  Note down these credentials.
5.  Click **Enter phpMyAdmin** next to your new database.
6.  In phpMyAdmin, go to the **Import** tab.
7.  Upload the `hostinger_db.sql` file from this project.
8.  Click **Go** to import the tables.

## 3. Upload Files
1.  Go to **Files** > **File Manager** in Hostinger.
2.  Navigate to `public_html`.
3.  Delete the default `default.php` if it exists.
4.  Upload your `.zip` file.
5.  Right-click the zip file and select **Extract**.
6.  Move files if they extracted into a subfolder (ensure `index.php` is directly in `public_html` or your desired subfolder).

## 4. Final Configuration
1.  Ensure you updated `config/db.php` with the credentials from Step 2.
2.  Check permissions:
    *   The `uploads/` folder needs "Write" permissions.
    *   Right-click `uploads` > **Permissions** > Set to `755` or `777` if strict.

## 5. Voice Detection
*   Voice detection requires microphone access.
*   **Important**: Browsers only allow microphone access on **HTTPS** sites.
*   Ensure your Hostinger SSL is active (usually free and automatic).

## 6. Access
Visit your domain (e.g., `https://your-domain.com`) to see the system running!

