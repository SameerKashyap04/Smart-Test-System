IMPORTANT SECURITY INSTRUCTIONS
===============================

Your system is now protected.

1.  **Source Code Protection:**
    - The file `config/credentials.php` contains your database passwords and a secret license key.
    - This file is IGNORED by git. It will NOT be uploaded to GitHub.
    - Anyone who downloads your code from GitHub will get an error: "Configuration Missing".

2.  **Server Deployment (CRITICAL):**
    - Because `config/credentials.php` is not in GitHub, your website on Hostinger will STOP WORKING after you push these changes.
    - TO FIX IT: You must manually upload the `config/credentials.php` file from your local computer to your Hostinger server using the File Manager.
    - Path: `domains/devify.live/public_html/config/credentials.php`

3.  **To allow others to work on it (Optional):**
    - If you want a specific developer to work on it, send them the `config/credentials.php` file privately. Do NOT put it in the repo.

4.  **Database Password:**
    - Since your password was previously in the code history, it is recommended to CHANGE your database password in the Hostinger control panel.
    - After changing it, update `config/credentials.php` with the new password.

5.  **GitHub Privacy:**
    - Go to your GitHub repository settings and change Visibility to **Private**.

