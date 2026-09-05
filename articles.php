<?php
/**
 * SEO Articles & Guides Hub
 * Tailor Business Management & Marketing System
 */

$page_title = 'Uniform Procurement Guides & Tailoring Insights';
$page_desc = 'Expert guides on bulk school uniform procurement in Kenya, color-fast fabric selection, bespoke suit fitting, and corporate dress codes.';

require_once __DIR__ . '/includes/header.php';

// Fetch published articles
$articles = db_fetch_all("
    SELECT a.*, u.full_name as author_name 
    FROM articles a 
    LEFT JOIN users u ON a.author_id = u.id 
    WHERE a.is_published = 1 
    ORDER BY a.published_at DESC
");
?>

<section class="bg-dark text-white py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
  <div class="container py-2 text-center">
    <span class="badge bg-primary px-3 py-2 rounded-pill fw-semibold mb-2">Knowledge Hub</span>
    <h1 class="display-5 fw-bold">Tailoring Insights & Procurement Guides</h1>
    <p class="lead text-secondary max-w-700 mx-auto">
      Essential advice for school administrators, corporate procurement committees, and discerning gentlemen.
    </p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row g-4">
      <?php if (!empty($articles)): ?>
        <?php foreach ($articles as $article): ?>
          <div class="col-md-6">
            <div class="card h-100 border shadow-sm rounded-4 overflow-hidden">
              <div class="card-body p-4 d-flex flex-direction-column flex-column justify-content-between">
                <div>
                  <div class="d-flex justify-content-between align-items-center small text-muted mb-2">
                    <span><i class="bi bi-calendar3 me-1"></i> <?= format_date($article['published_at']) ?></span>
                    <span><i class="bi bi-eye me-1"></i> <?= (int)$article['views_count'] ?> views</span>
                  </div>
                  <h3 class="h4 fw-bold mb-3">
                    <a href="<?= BASE_URL ?>/article_view.php?slug=<?= urlencode($article['slug']) ?>" class="text-dark text-decoration-none hover-primary">
                      <?= e($article['title']) ?>
                    </a>
                  </h3>
                  <p class="text-muted mb-4">
                    <?= e($article['excerpt']) ?>
                  </p>
                </div>
                
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                  <span class="small fw-semibold text-secondary">
                    <i class="bi bi-person-circle me-1"></i> <?= e($article['author_name'] ?? 'Master Tailor') ?>
                  </span>
                  <a href="<?= BASE_URL ?>/article_view.php?slug=<?= urlencode($article['slug']) ?>" class="btn btn-outline-primary btn-sm fw-semibold">
                    Read Complete Guide <i class="bi bi-arrow-right ms-1"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12 text-center text-muted py-5">
          <p>No guides published yet.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
