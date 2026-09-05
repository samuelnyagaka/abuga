<?php
/**
 * Single Article Viewer with SEO Meta & Lead Gen CTA
 * Tailor Business Management & Marketing System
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$slug = sanitize($_GET['slug'] ?? '');
if (empty($slug)) {
    header("Location: " . BASE_URL . "/articles.php");
    exit;
}

$article = db_fetch_one("
    SELECT a.*, u.full_name as author_name 
    FROM articles a 
    LEFT JOIN users u ON a.author_id = u.id 
    WHERE a.slug = ? AND a.is_published = 1 
    LIMIT 1
", [$slug]);

if (!$article) {
    header("Location: " . BASE_URL . "/articles.php");
    exit;
}

// Increment view counter
db_query("UPDATE articles SET views_count = views_count + 1 WHERE id = ?", [$article['id']]);

$page_title = $article['meta_title'] ?: $article['title'];
$page_desc = $article['meta_description'] ?: $article['excerpt'];

require_once __DIR__ . '/includes/header.php';
?>

<article class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/articles.php" class="text-decoration-none">Guides</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($article['title']) ?></li>
          </ol>
        </nav>

        <!-- Header -->
        <h1 class="display-6 fw-bold mb-3"><?= e($article['title']) ?></h1>
        
        <div class="d-flex flex-wrap align-items-center gap-3 text-muted small pb-3 mb-4 border-bottom">
          <span><i class="bi bi-person me-1"></i> <?= e($article['author_name'] ?? 'Master Tailor') ?></span>
          <span>&bull;</span>
          <span><i class="bi bi-calendar3 me-1"></i> <?= format_date($article['published_at']) ?></span>
          <span>&bull;</span>
          <span><i class="bi bi-eye me-1"></i> <?= (int)$article['views_count'] + 1 ?> views</span>
        </div>

        <!-- Excerpt Callout -->
        <div class="p-4 bg-light rounded-3 mb-4 fst-italic border-start border-primary border-4 text-secondary">
          <?= e($article['excerpt']) ?>
        </div>

        <!-- Article Body -->
        <div class="article-content fs-6 lh-lg mb-5" style="color: #334155;">
          <?= $article['body_content'] // Rich HTML content authored by admin ?>
        </div>

        <!-- Social Share & WhatsApp CTA -->
        <div class="p-4 rounded-4 bg-light border mb-5">
          <div class="row align-items-center">
            <div class="col-md-6 mb-3 mb-md-0">
              <h6 class="fw-bold mb-1">Share This Guide:</h6>
              <div class="d-flex gap-2">
                <a href="https://api.whatsapp.com/send?text=<?= urlencode($article['title'] . ' ' . BASE_URL . '/article_view.php?slug=' . $article['slug']) ?>" 
                   target="_blank" class="btn btn-sm btn-success">
                  <i class="bi bi-whatsapp me-1"></i> WhatsApp
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(BASE_URL . '/article_view.php?slug=' . $article['slug']) ?>" 
                   target="_blank" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-facebook me-1"></i> Share
                </a>
                <a href="https://twitter.com/intent/tweet?text=<?= urlencode($article['title']) ?>&url=<?= urlencode(BASE_URL . '/article_view.php?slug=' . $article['slug']) ?>" 
                   target="_blank" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-twitter-x me-1"></i> Post
                </a>
              </div>
            </div>
            <div class="col-md-6 text-md-end">
              <a href="<?= BASE_URL ?>/bulk_inquiry.php" class="btn btn-gold btn-sm px-3 py-2 fw-semibold">
                <i class="bi bi-briefcase me-1"></i> Request Uniform Tender Quote
              </a>
            </div>
          </div>
        </div>

        <!-- Back to guides link -->
        <div class="text-center">
          <a href="<?= BASE_URL ?>/articles.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to All Articles
          </a>
        </div>

      </div>
    </div>
  </div>
</article>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
