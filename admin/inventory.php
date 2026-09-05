<?php
/**
 * Inventory Manager (Fabrics & Trims)
 * Tailor Business Management & Marketing System
 */

$admin_page_title = 'Inventory & Materials';
require_once __DIR__ . '/admin_header.php';

// Handle Add Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_item'])) {
    $sku_code          = sanitize($_POST['sku_code'] ?? '');
    $item_name         = sanitize($_POST['item_name'] ?? '');
    $category          = sanitize($_POST['category'] ?? 'fabric');
    $color_pattern     = sanitize($_POST['color_pattern'] ?? '');
    $quantity          = (float)($_POST['quantity_on_hand'] ?? 0);
    $unit              = sanitize($_POST['unit'] ?? 'meters');
    $reorder_threshold = (float)($_POST['reorder_threshold'] ?? 10);
    $cost_per_unit     = (float)($_POST['cost_per_unit'] ?? 0);
    $supplier_name     = sanitize($_POST['supplier_name'] ?? '');
    $supplier_phone    = sanitize($_POST['supplier_phone'] ?? '');

    if (empty($sku_code) || empty($item_name)) {
        set_flash_message('danger', 'SKU Code and Item Name are required.');
    } else {
        $insertId = db_insert('inventory', [
            'sku_code'          => $sku_code,
            'item_name'         => $item_name,
            'category'          => $category,
            'color_pattern'     => $color_pattern,
            'quantity_on_hand'  => $quantity,
            'unit'              => $unit,
            'reorder_threshold' => $reorder_threshold,
            'cost_per_unit'     => $cost_per_unit,
            'supplier_name'     => $supplier_name,
            'supplier_phone'    => $supplier_phone
        ]);

        if ($insertId) {
            set_flash_message('success', "Item {$item_name} added to inventory.");
        } else {
            set_flash_message('danger', 'Failed to add item (SKU code might already exist).');
        }
    }
    header("Location: " . BASE_URL . "/admin/inventory.php");
    exit;
}

// Handle Adjust Stock Quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_adjust_stock'])) {
    $item_id = (int)$_POST['item_id'];
    $adjust_type = $_POST['adjust_type']; // 'add' or 'subtract' or 'set'
    $adjust_qty  = (float)$_POST['adjust_qty'];

    if ($item_id && $adjust_qty > 0) {
        if ($adjust_type === 'add') {
            db_query("UPDATE inventory SET quantity_on_hand = quantity_on_hand + ? WHERE id = ?", [$adjust_qty, $item_id]);
        } elseif ($adjust_type === 'subtract') {
            db_query("UPDATE inventory SET quantity_on_hand = GREATEST(0, quantity_on_hand - ?) WHERE id = ?", [$adjust_qty, $item_id]);
        } else {
            db_query("UPDATE inventory SET quantity_on_hand = ? WHERE id = ?", [$adjust_qty, $item_id]);
        }
        set_flash_message('success', 'Stock level updated.');
    }
    header("Location: " . BASE_URL . "/admin/inventory.php");
    exit;
}

// Fetch Inventory Items
$category_filter = sanitize($_GET['category'] ?? '');
$search = sanitize($_GET['q'] ?? '');

$sql = "SELECT * FROM inventory WHERE 1=1";
$params = [];

if (!empty($category_filter)) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
}

if (!empty($search)) {
    $sql .= " AND (sku_code LIKE ? OR item_name LIKE ? OR supplier_name LIKE ?)";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}

$sql .= " ORDER BY quantity_on_hand <= reorder_threshold DESC, item_name ASC";
$items = db_fetch_all($sql, $params);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-1">Fabrics & Trims Inventory</h4>
    <p class="text-muted small mb-0">Track raw materials stock, meterage on hand, and low-stock reorder points.</p>
  </div>
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addItemModal">
    <i class="bi bi-plus-lg me-1"></i> Add Material / Trim
  </button>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
  <div class="card-body p-3">
    <form method="GET" action="<?= BASE_URL ?>/admin/inventory.php" class="row g-2 align-items-center">
      <div class="col-md-7">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input type="text" name="q" class="form-control" placeholder="Search by SKU, fabric name, or supplier..." value="<?= e($search) ?>">
        </div>
      </div>
      <div class="col-md-3">
        <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">-- All Material Categories --</option>
          <option value="fabric" <?= $category_filter == 'fabric' ? 'selected' : '' ?>>Fabrics</option>
          <option value="lining" <?= $category_filter == 'lining' ? 'selected' : '' ?>>Linings</option>
          <option value="buttons" <?= $category_filter == 'buttons' ? 'selected' : '' ?>>Buttons</option>
          <option value="zipper" <?= $category_filter == 'zipper' ? 'selected' : '' ?>>Zippers</option>
          <option value="thread" <?= $category_filter == 'thread' ? 'selected' : '' ?>>Threads / Spools</option>
          <option value="elastic" <?= $category_filter == 'elastic' ? 'selected' : '' ?>>Elastics</option>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-outline-primary btn-sm w-100">Filter</button>
      </div>
    </form>
  </div>
</div>

<!-- Inventory Table -->
<div class="card border-0 shadow-sm rounded-3">
  <div class="table-responsive">
    <table class="table admin-table mb-0">
      <thead>
        <tr>
          <th>SKU / Code</th>
          <th>Item Description</th>
          <th>Category</th>
          <th>Color / Pattern</th>
          <th>Quantity On Hand</th>
          <th>Reorder Level</th>
          <th>Supplier</th>
          <th>Stock Status</th>
          <th>Quick Adjust</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($items)): ?>
          <?php foreach ($items as $item): ?>
            <?php 
              $is_low = $item['quantity_on_hand'] <= $item['reorder_threshold'];
            ?>
            <tr class="<?= $is_low ? 'table-warning bg-opacity-25' : '' ?>">
              <td><span class="badge bg-secondary font-monospace"><?= e($item['sku_code']) ?></span></td>
              <td>
                <div class="fw-bold text-dark"><?= e($item['item_name']) ?></div>
                <?php if (has_role('admin') && $item['cost_per_unit'] > 0): ?>
                  <small class="text-muted">Cost: <?= format_currency($item['cost_per_unit']) ?> / <?= e($item['unit']) ?></small>
                <?php endif; ?>
              </td>
              <td><span class="badge bg-light text-dark border text-capitalize"><?= e($item['category']) ?></span></td>
              <td><?= e($item['color_pattern'] ?: '-') ?></td>
              <td class="fw-bold fs-6 <?= $is_low ? 'text-danger' : 'text-dark' ?>">
                <?= (float)$item['quantity_on_hand'] ?> <small class="fw-normal text-muted"><?= e($item['unit']) ?></small>
              </td>
              <td><?= (float)$item['reorder_threshold'] ?> <?= e($item['unit']) ?></td>
              <td>
                <div class="small fw-semibold"><?= e($item['supplier_name'] ?: 'Local') ?></div>
                <?php if ($item['supplier_phone']): ?>
                  <small class="text-muted"><a href="tel:<?= e($item['supplier_phone']) ?>" class="text-decoration-none"><?= e($item['supplier_phone']) ?></a></small>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($is_low): ?>
                  <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i> Low Stock</span>
                <?php else: ?>
                  <span class="badge bg-success">In Stock</span>
                <?php endif; ?>
              </td>
              <td>
                <!-- Quick Adjust Form -->
                <form method="POST" action="" class="d-flex align-items-center gap-1">
                  <input type="hidden" name="action_adjust_stock" value="1">
                  <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                  <input type="number" step="1" name="adjust_qty" class="form-control form-control-sm text-center" style="width: 65px;" value="10" min="1">
                  <button type="submit" name="adjust_type" value="add" class="btn btn-outline-success btn-sm px-2" title="Add Stock">+</button>
                  <button type="submit" name="adjust_type" value="subtract" class="btn btn-outline-danger btn-sm px-2" title="Deduct Stock">-</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="9" class="text-center text-muted py-5">No inventory items found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Add Inventory Item -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="">
        <input type="hidden" name="action_add_item" value="1">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Add Fabric or Trim</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">SKU Code <span class="text-danger">*</span></label>
              <input type="text" name="sku_code" class="form-control" placeholder="e.g. FAB-NVY-05" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Category</label>
              <select name="category" class="form-select">
                <option value="fabric">Fabric</option>
                <option value="lining">Lining</option>
                <option value="buttons">Buttons</option>
                <option value="zipper">Zippers</option>
                <option value="thread">Thread</option>
                <option value="elastic">Elastic</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Item Name & Blend <span class="text-danger">*</span></label>
            <input type="text" name="item_name" class="form-control" placeholder="e.g. Poly-Viscose School Navy Twill" required>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Color / Pattern</label>
              <input type="text" name="color_pattern" class="form-control" placeholder="e.g. Dark Navy Blue">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Initial Qty</label>
              <input type="number" step="0.5" name="quantity_on_hand" class="form-control" value="50" min="0">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Unit</label>
              <select name="unit" class="form-select">
                <option value="meters" selected>Meters</option>
                <option value="rolls">Rolls</option>
                <option value="pieces">Pieces</option>
                <option value="spools">Spools</option>
                <option value="boxes">Boxes</option>
              </select>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Reorder Alert Threshold</label>
              <input type="number" step="0.5" name="reorder_threshold" class="form-control" value="20" min="1">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Cost Per Unit (KES)</label>
              <input type="number" step="0.01" name="cost_per_unit" class="form-control" placeholder="480.00">
            </div>
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Supplier Name</label>
              <input type="text" name="supplier_name" class="form-control" placeholder="e.g. Rivatex East Africa">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Supplier Phone</label>
              <input type="tel" name="supplier_phone" class="form-control" placeholder="0700 000 000">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold">Save Material</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
