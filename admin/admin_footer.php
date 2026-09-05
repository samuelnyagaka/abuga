<?php
/**
 * Admin Panel Footer Template
 * Tailor Business Management & Marketing System
 */
?>
    </main>
  </div>

  <!-- Reusable Action Confirmation Modal -->
  <div class="modal fade" id="actionConfirmModal" tabindex="-1" aria-labelledby="actionConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="modal-header border-0 pt-4 px-4 pb-0">
          <div class="d-flex align-items-center gap-3">
            <div id="actionConfirmIconBox" class="rounded-circle d-flex align-items-center justify-content-center bg-warning-subtle text-warning flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.35rem;">
              <i class="bi bi-exclamation-triangle-fill" id="actionConfirmIcon"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-dark mb-0" id="actionConfirmModalLabel">Confirm Action</h5>
              <small class="text-muted">Please confirm before proceeding</small>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body px-4 py-3">
          <p id="actionConfirmMessage" class="text-secondary mb-0 fs-6">Are you sure you want to perform this action?</p>
        </div>
        <div class="modal-footer border-0 pb-4 px-4 pt-1 d-flex justify-content-end gap-2 bg-light-subtle">
          <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Cancel</button>
          <a href="#" id="actionConfirmSubmitBtn" class="btn btn-sm btn-danger px-3 fw-semibold">Yes, Proceed</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Admin Custom JS with Cache-Busting -->
  <script src="<?= asset_url('assets/js/admin.js') ?>"></script>
</body>
</html>
