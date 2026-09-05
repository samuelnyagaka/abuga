<?php
/**
 * Website Content CMS Manager
 * Tailor Business Management & Marketing System
 */

$admin_page_title = 'Website Content Manager';
require_once __DIR__ . '/admin_header.php';

// Admin permission check
require_admin_role();

$tab = sanitize($_GET['tab'] ?? 'gallery');

// 1. Handle Gallery Item Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_gallery'])) {
    $title       = sanitize($_POST['title'] ?? '');
    $category    = sanitize($_POST['category'] ?? 'school_uniforms');
    $description = sanitize($_POST['description'] ?? '');
    $is_featured = !empty($_POST['is_featured']) ? 1 : 0;

    if (!empty($_FILES['image']['name']) && !empty($title)) {
        $uploadResult = upload_and_resize_image($_FILES['image'], 'gallery', 1200, 82);
        if ($uploadResult['success']) {
            db_insert('gallery', [
                'title'       => $title,
                'category'    => $category,
                'image_path'  => $uploadResult['path'],
                'description' => $description,
                'is_featured' => $is_featured,
                'sort_order'  => 0
            ]);
            set_flash_message('success', 'New gallery photo added.');
        } else {
            set_flash_message('danger', 'Image upload failed: ' . $uploadResult['error']);
        }
    } else {
        set_flash_message('danger', 'Please enter a title and select an image.');
    }
    header("Location: " . BASE_URL . "/admin/content_manager.php?tab=gallery");
    exit;
}

// Handle Delete Gallery Item
if (isset($_GET['delete_gallery'])) {
    $delId = (int)$_GET['delete_gallery'];
    db_delete('gallery', 'id = ?', [$delId]);
    set_flash_message('success', 'Gallery item removed.');
    header("Location: " . BASE_URL . "/admin/content_manager.php?tab=gallery");
    exit;
}

// 2. Handle Testimonial Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_testimonial'])) {
    $author = sanitize($_POST['author_name'] ?? '');
    $role   = sanitize($_POST['organization_title'] ?? '');
    $quote  = sanitize($_POST['quote_text'] ?? '');
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));

    if (!empty($author) && !empty($quote)) {
        db_insert('testimonials', [
            'author_name'        => $author,
            'organization_title' => $role,
            'quote_text'         => $quote,
            'rating'             => $rating,
            'is_approved'        => 1
        ]);
        set_flash_message('success', 'Testimonial added.');
    }
    header("Location: " . BASE_URL . "/admin/content_manager.php?tab=testimonials");
    exit;
}

// Handle Delete Testimonial
if (isset($_GET['delete_testimonial'])) {
    $delId = (int)$_GET['delete_testimonial'];
    db_delete('testimonials', 'id = ?', [$delId]);
    set_flash_message('success', 'Testimonial deleted.');
    header("Location: " . BASE_URL . "/admin/content_manager.php?tab=testimonials");
    exit;
}

// 3. Handle Article Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_article'])) {
    $title    = sanitize($_POST['title'] ?? '');
    $slug     = preg_replace('/[^a-z0-9-]+/', '-', strtolower(trim($title)));
    $excerpt  = sanitize($_POST['excerpt'] ?? '');
    $body     = $_POST['body_content'] ?? '';
    $meta_t   = sanitize($_POST['meta_title'] ?? $title);
    $meta_d   = sanitize($_POST['meta_description'] ?? $excerpt);

    if (!empty($title) && !empty($body)) {
        db_insert('articles', [
            'title'            => $title,
            'slug'             => $slug,
            'excerpt'          => $excerpt,
            'body_content'     => $body,
            'meta_title'       => $meta_t,
            'meta_description' => $meta_d,
            'is_published'     => 1,
            'author_id'        => $user['id']
        ]);
        set_flash_message('success', 'Article published successfully.');
    }
    header("Location: " . BASE_URL . "/admin/content_manager.php?tab=articles");
    exit;
}

// Handle Delete Article
if (isset($_GET['delete_article'])) {
    $delId = (int)$_GET['delete_article'];
    db_delete('articles', 'id = ?', [$delId]);
    set_flash_message('success', 'Article deleted.');
    header("Location: " . BASE_URL . "/admin/content_manager.php?tab=articles");
    exit;
}

// Fetch Records for current tab
$gallery_items = db_fetch_all("SELECT * FROM gallery ORDER BY id DESC");
$testimonials  = db_fetch_all("SELECT * FROM testimonials ORDER BY id DESC");
$articles      = db_fetch_all("SELECT * FROM articles ORDER BY id DESC");
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Website Content Management (CMS)</h4>
    <p class="text-muted small mb-0">Update gallery showcases, client reviews, and SEO blog articles.</p>
  </div>
  <a href="<?= BASE_URL ?>/index.php" target="_blank" class="btn btn-outline-primary btn-sm">
    <i class="bi bi-box-arrow-up-right me-1"></i> Preview Live Site
  </a>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-4">
  <li class="nav-item">
    <a class="nav-link <?= $tab == 'gallery' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/content_manager.php?tab=gallery">
      <i class="bi bi-images me-1"></i> Gallery Showcase (<?= count($gallery_items) ?>)
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab == 'testimonials' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/content_manager.php?tab=testimonials">
      <i class="bi bi-chat-quote me-1"></i> Client Reviews (<?= count($testimonials) ?>)
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab == 'articles' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/content_manager.php?tab=articles">
      <i class="bi bi-journal-text me-1"></i> Guides & Articles (<?= count($articles) ?>)
    </a>
  </li>
</ul>

<?php if ($tab == 'gallery'): ?>
  <!-- Gallery Tab -->
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Upload Gallery Photo</h6>
        <form method="POST" action="" enctype="multipart/form-data">
          <input type="hidden" name="action_add_gallery" value="1">
          <div class="mb-3">
            <label class="form-label small fw-bold">Garment Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control form-control-sm" placeholder="e.g. Senior High School Blazer" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Category</label>
            <select name="category" class="form-select form-select-sm">
              <option value="school_uniforms">School Uniforms</option>
              <option value="corporate_uniforms">Corporate Attire</option>
              <option value="security_medical">Security & Workwear</option>
              <option value="suits_blazers">Bespoke Suits</option>
              <option value="dresses_traditional">African & Ceremonial</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Description</label>
            <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Fabric, crest details, fit specifications..."></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Select Photo <span class="text-danger">*</span></label>
            <input type="file" name="image" class="form-control form-control-sm" accept="image/*" required>
            <div class="form-text small">Automatically compressed via GD to save disk quota.</div>
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="featCheck" checked>
            <label class="form-check-label small" for="featCheck">Display on Homepage</label>
          </div>
          <button type="submit" class="btn btn-primary btn-sm w-100">Upload Photo</button>
        </form>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Active Portfolio Items</h6>
        <div class="row g-3">
          <?php foreach ($gallery_items as $g): ?>
            <div class="col-md-6 col-xl-4">
              <div class="border rounded-3 overflow-hidden">
                <img src="<?= BASE_URL ?>/<?= e($g['image_path']) ?>" alt="<?= e($g['title']) ?>" style="height: 140px; width: 100%; object-fit: cover;" onerror="this.src='https://placehold.co/400x250/0f172a/white?text=Sample'">
                <div class="p-2">
                  <div class="fw-bold small text-truncate"><?= e($g['title']) ?></div>
                  <div class="small text-muted text-capitalize"><?= e(str_replace('_', ' ', $g['category'])) ?></div>
                  <div class="d-flex justify-content-between align-items-center mt-2 pt-1 border-top">
                    <?php if ($g['is_featured']): ?>
                      <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">Featured</span>
                    <?php else: ?>
                      <span></span>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/admin/content_manager.php?tab=gallery&delete_gallery=<?= $g['id'] ?>" 
                       class="text-danger small btn-confirm-action" 
                       data-title="Delete Gallery Showcase Photo"
                       data-message="Are you sure you want to permanently remove this photo from the public showcase gallery?"
                       data-confirm-text="Yes, Delete Photo"
                       data-confirm-class="btn-danger"
                       data-href="<?= BASE_URL ?>/admin/content_manager.php?tab=gallery&delete_gallery=<?= $g['id'] ?>">
                      <i class="bi bi-trash"></i> Delete
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

<?php elseif ($tab == 'testimonials'): ?>
  <!-- Testimonials Tab -->
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Add Client Review</h6>
        <form method="POST" action="">
          <input type="hidden" name="action_add_testimonial" value="1">
          <div class="mb-3">
            <label class="form-label small fw-bold">Client / Principal Name <span class="text-danger">*</span></label>
            <input type="text" name="author_name" class="form-control form-control-sm" placeholder="e.g. Sister Rose Wanjiru" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Role & Organization</label>
            <input type="text" name="organization_title" class="form-control form-control-sm" placeholder="Principal, St. Jude Girls High School">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Review Quote <span class="text-danger">*</span></label>
            <textarea name="quote_text" class="form-control form-control-sm" rows="3" placeholder="Client feedback regarding fabric durability, delivery speed, or fit..." required></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Star Rating</label>
            <select name="rating" class="form-select form-select-sm">
              <option value="5" selected>5 Stars (Excellent)</option>
              <option value="4">4 Stars (Good)</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary btn-sm w-100">Save Testimonial</button>
        </form>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Approved Reviews</h6>
        <div class="list-group list-group-flush">
          <?php foreach ($testimonials as $t): ?>
            <div class="list-group-item px-0 py-3 border-bottom">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                  <h6 class="mb-0 fw-bold"><?= e($t['author_name']) ?></h6>
                  <small class="text-muted"><?= e($t['organization_title']) ?></small>
                </div>
                <div class="text-warning small">
                  <?php for ($i=0; $i<$t['rating']; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
                </div>
              </div>
              <p class="small text-secondary fst-italic mb-2">"<?= e($t['quote_text']) ?>"</p>
              <div class="text-end">
                <a href="<?= BASE_URL ?>/admin/content_manager.php?tab=testimonials&delete_testimonial=<?= $t['id'] ?>" 
                   class="text-danger small btn-confirm-action" 
                   data-title="Delete Client Testimonial"
                   data-message="Are you sure you want to permanently remove this testimonial from '<?= e($t['client_name']) ?>'?"
                   data-confirm-text="Yes, Remove Review"
                   data-confirm-class="btn-danger"
                   data-href="<?= BASE_URL ?>/admin/content_manager.php?tab=testimonials&delete_testimonial=<?= $t['id'] ?>">
                  <i class="bi bi-trash"></i> Remove
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

<?php elseif ($tab == 'articles'): ?>
  <!-- Articles Tab -->
  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Publish SEO Article</h6>
        <form method="POST" action="">
          <input type="hidden" name="action_add_article" value="1">
          <div class="mb-3">
            <label class="form-label small fw-bold">Article Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control form-control-sm" placeholder="e.g. 5 Fabric Tests for School Uniform Tenders" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Short Excerpt <span class="text-danger">*</span></label>
            <textarea name="excerpt" class="form-control form-control-sm" rows="2" placeholder="Summary shown on article card..." required></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Article Body Content (HTML supported) <span class="text-danger">*</span></label>
            <textarea name="body_content" class="form-control form-control-sm" rows="6" placeholder="Write guide content using <p>, <h3>, <ul> tags..." required></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">SEO Meta Description</label>
            <input type="text" name="meta_description" class="form-control form-control-sm" placeholder="150 characters for Google search snippet">
          </div>
          <button type="submit" class="btn btn-primary btn-sm w-100">Publish Article</button>
        </form>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Published Articles (<?= count($articles) ?>)</h6>
        <div class="list-group list-group-flush">
          <?php foreach ($articles as $a): ?>
            <div class="list-group-item px-0 py-3 border-bottom">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <h6 class="mb-0 fw-bold">
                  <a href="<?= BASE_URL ?>/article_view.php?slug=<?= urlencode($a['slug']) ?>" target="_blank" class="text-dark text-decoration-none">
                    <?= e($a['title']) ?>
                  </a>
                </h6>
                <span class="small text-muted"><?= (int)$a['views_count'] ?> views</span>
              </div>
              <p class="small text-muted mb-2"><?= e($a['excerpt']) ?></p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="small text-muted">Published: <?= format_date($a['published_at']) ?></span>
                <a href="<?= BASE_URL ?>/admin/content_manager.php?tab=articles&delete_article=<?= $a['id'] ?>" 
                   class="text-danger small btn-confirm-action" 
                   data-title="Delete Published Article"
                   data-message="Are you sure you want to delete the article '<?= e($a['title']) ?>'?"
                   data-confirm-text="Yes, Delete Article"
                   data-confirm-class="btn-danger"
                   data-href="<?= BASE_URL ?>/admin/content_manager.php?tab=articles&delete_article=<?= $a['id'] ?>">
                  <i class="bi bi-trash"></i> Delete
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

<?php endif; ?>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
