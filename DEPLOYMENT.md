# InfinityFree Deployment Guide
## Tailor Business Management & Marketing System

This system is specially engineered to run seamlessly on **InfinityFree Free Hosting** without requiring SSH access, Composer, or shell build pipelines.

---

### Step 1: Create Your MySQL Database in InfinityFree vPanel

1. Log in to your [InfinityFree Client Area](https://dash.infinityfree.com/) and open your account's **Control Panel (vPanel)**.
2. Under the **Databases** section, click on **MySQL Databases**.
3. Create a new database (e.g. `if0_12345678_tailor`).
4. Note down your connection credentials displayed on that screen:
   - **MySQL Hostname:** (e.g. `sql300.infinityfree.com` &mdash; *do NOT use 'localhost' on InfinityFree*)
   - **MySQL Database Name:** (e.g. `if0_12345678_tailor`)
   - **MySQL Username:** (e.g. `if0_12345678_tailor`)
   - **MySQL Password:** (your vPanel hosting account password)
   - **MySQL Port:** `3306`

---

### Step 2: Import Database Schema via phpMyAdmin

1. In the InfinityFree vPanel, click **phpMyAdmin** next to your newly created database.
2. Once phpMyAdmin loads, select your database from the left-hand menu.
3. Click the **Import** tab in the top navigation bar.
4. Click **Choose File** and select `database/schema.sql` from this project.
5. Click **Go** at the bottom.
   > All 16 tables and initial seed data (admin accounts, sample school uniform tenders, fabrics, and articles) will be created immediately.

---

### Step 3: Configure Database Credentials in PHP

Open `config/database.php` on your computer or directly in the file manager and update the credentials:

```php
define('DB_HOST', 'sql300.infinityfree.com'); // Your InfinityFree MySQL Host
define('DB_USER', 'if0_12345678');           // Your InfinityFree MySQL Username
define('DB_PASS', 'YourVpanelPassword');     // Your InfinityFree Account Password
define('DB_NAME', 'if0_12345678_tailor');    // Your InfinityFree Database Name
define('DB_PORT', 3306);
```

---

### Step 4: Upload Files via FTP (FileZilla) or File Manager

1. Open your FTP client (such as **FileZilla**) or open the **Monsta File Manager** in vPanel.
2. In FileZilla, enter:
   - **Host:** `ftpupload.net`
   - **Username:** Your InfinityFree FTP username
   - **Password:** Your vPanel password
   - **Port:** `21`
3. Navigate into the **`htdocs/`** directory.
4. Upload all files and folders from this project directly into `htdocs/` such that `index.php` is located at `htdocs/index.php`.
5. Ensure the **`uploads/`** directory is writable (`chmod 755`).

---

### Step 5: Brevo (Sendinblue) HTTPS API Configuration

> **Note on InfinityFree Mail Restrictions:** Standard PHP `mail()` and outgoing SMTP ports (25, 465, 587) are disabled on InfinityFree free tier. This system uses the **Brevo v3 HTTPS REST API (cURL)** instead.

1. Sign up for a free account at [brevo.com](https://www.brevo.com/).
2. Go to **Settings &rarr; SMTP & API Keys** and click **Generate a new API key**.
3. In your website, log in to the admin portal:
   - URL: `https://yourdomain.com/admin/login.php`
   - Default Username: `admin`
   - Default Password: `admin123`
4. Go to **System Settings** in the sidebar.
5. Paste your Brevo v3 API Key and your verified sender email address, then click **Save**.
   *(If left blank, quote confirmations will still be recorded and logged to `uploads/email_simulated.log` without crashing).*

---

### Step 6: Automated Reminders via cron-job.org

> **Note on Cron Restrictions:** InfinityFree free tier has no background cron daemon.

1. Create a free account at [cron-job.org](https://cron-job.org).
2. Click **Create Cronjob**.
3. In the **URL** field, enter your dispatcher link:
   `https://yourdomain.com/cron_dispatch.php?token=tailor_cron_secure_token_2026`
   *(You can change `tailor_cron_secure_token_2026` in `admin/settings.php`)*.
4. Set the schedule to run **Every day at 08:00 AM** (Nairobi time).
5. Whenever pinged, the script will automatically:
   - Check leads needing follow-up today
   - Check orders due in 3 days
   - Check low fabric stock
   - Dispatch an operations alert email to you via Brevo!

---

### Step 7: Keyword Filter Protection Note

InfinityFree uses an aggressive Web Application Firewall (WAF) that blocks URL requests containing trigger words like `chat.php`, `proxy.php`, or `shell.php`. 
This codebase has been strictly designed with neutral, compliant filenames:
- WhatsApp chats use `https://wa.me/` direct API links
- Inquiries are handled via `inquiries.php` and `bulk_inquiry.php`
- Administrative endpoints use neutral, business-standard names

---

### Default Login Accounts

The unified login portal at `/login.php` automatically detects the user's role and routes them accordingly:

| Role | Username / Email | Password | Access Level & Destination |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | Full access to finances, settings, staff, CMS, orders (`/admin/dashboard.php`) |
| **Staff / Cutter** | `tailor_john` | `staff123` | Operational access to orders, sizing, delivery batches (`/admin/dashboard.php`) |
| **Individual Client** | `evans.mutua@knh.or.ke` | `customer123` | Marketplace customer portal: orders, sizing & quotes (`/my_account.php`) |
| **School Partner** | `procurement@staugustine.ac.ke` | `school123` | Institutional portal: bulk batches, measurements & tenders (`/my_account.php`) |

> **Customer Self-Registration:** Customers can register at any time at `/register.php` as either an **Individual Client** or **Institution (School / Corporate)**.
