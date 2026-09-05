# StitchCraft & Uniform Masters — Business Management & Marketing System

A full-stack business management and digital marketing platform built in native **PHP** and **MySQL**, specifically engineered to operate within **InfinityFree Shared Free Hosting** constraints while delivering enterprise-grade operational power for school and corporate uniform production and bespoke tailoring.

---

## Key Modules & Features

### 1. Public Marketing Website
- **Mobile-First Responsive Layout:** Bootstrap 5, custom clean CSS, and Google Fonts (`Outfit` & `Inter`).
- **Institutional & Bespoke Portals:** Dedicated pages for [School & Corporate Uniforms](services_uniforms.php) and [Bespoke Custom Suits](services_custom.php).
- **Interactive Price Estimator:** Real-time tier discount calculator on the homepage for bulk school orders.
- **Categorized Portfolio:** Lightbox modal preview with direct WhatsApp order triggers on each garment.
- **Knowledge & Guides Hub:** SEO articles for tender procurement and bespoke fitting advice.
- **Local SEO & Schema Markup:** JSON-LD `ClothingStore` schema and OpenGraph social share cards.

### 2. Lead Generation Engine
- **Bulk Uniform Tender Form (`bulk_inquiry.php`):** Captures school name, estimated volume, target opening date, and logo/crest photo upload.
- **Custom Bespoke Quote Form (`quote_request.php`):** Captures individual measurements, budget tier, and style inspiration.
- **WhatsApp Integration:** Floating pulse chat button and contextual `wa.me` links across all cards.
- **Transactional Emails:** Outbound confirmations and admin notifications powered by the **Brevo (Sendinblue) HTTPS v3 API**.
- **Marketing Channel Attribution:** Automatically records lead origin (Google Search, Facebook, Instagram, TikTok, WhatsApp, Referral).

### 3. CRM & Order Pipeline
- **Institutional CRM:** Converts leads into active customer accounts in 1 click.
- **Stored Measurement Profiles:** Multiple profiles per customer in inches or cm with custom pattern notes.
- **Production Pipeline Stages:** Lead &rarr; Quoted &rarr; Confirmed &rarr; In Production &rarr; Ready &rarr; Completed.
- **Partial Delivery Tracking:** Log batch dispatches (e.g. *"120 of 300 delivered"*) with recipient sign-off names.
- **Printable Invoices & Quotes (`invoice.php`):** Clean, PDF-ready layouts with M-Pesa Paybill / Bank payment slips.
- **Materials Inventory (`inventory.php`):** Tracks fabrics (meters) and trims with low-stock warnings.

### 4. InfinityFree Hosting Optimizations
- **No Composer or Shell Requirement:** Native PHP + MySQLi prepared statements.
- **Brevo HTTPS API:** Solves blocked SMTP & PHP `mail()`.
- **External Cron Dispatcher (`cron_dispatch.php`):** Solves lack of cron on free tier.
- **Image Compression (GD):** Automatically resizes photos to stay under free storage quotas.
- **Asset Cache-Busting:** `filemtime()` query strings on all CSS/JS assets.
- **Keyword Filter Protected:** Zero trigger words in filenames.

---

## Quickstart & Local Setup

1. Import `database/schema.sql` into your local MySQL database (e.g. via phpMyAdmin or MySQL CLI).
2. Configure your DB credentials in `config/database.php`.
3. Launch with local PHP server (or Apache/XAMPP):
   ```bash
   php -S localhost:8000
   ```
4. Access:
   - Public Website: `http://localhost:8000/`
   - Admin Operations Portal: `http://localhost:8000/admin/login.php`

### Default Login Accounts:
- **Admin:** `admin` / `admin123` (Full access)
- **Staff:** `tailor_john` / `staff123` (Operational access)

---

## Deployment to InfinityFree

See the complete step-by-step instructions in [DEPLOYMENT.md](DEPLOYMENT.md).
