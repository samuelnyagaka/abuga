/**
 * StitchCraft Masters - Admin Dashboard JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  // Mobile Sidebar Toggle
  const sidebarToggle = document.getElementById('sidebarToggle');
  const adminSidebar = document.querySelector('.admin-sidebar');
  if (sidebarToggle && adminSidebar) {
    sidebarToggle.addEventListener('click', function () {
      adminSidebar.classList.toggle('show');
    });
  }

  // Dynamic Line Item Adder in Order Creation
  const addItemBtn = document.getElementById('btn_add_order_item');
  const itemsContainer = document.getElementById('order_items_container');

  function updateOrderTotals() {
    let grandTotal = 0;
    let totalItems = 0;

    const rows = document.querySelectorAll('.order-item-row');
    rows.forEach(row => {
      const qtyInput = row.querySelector('.item-qty');
      const priceInput = row.querySelector('.item-price');
      const subtotalOutput = row.querySelector('.item-subtotal');

      const qty = parseInt(qtyInput.value, 10) || 0;
      const price = parseFloat(priceInput.value) || 0;
      const subtotal = qty * price;

      totalItems += qty;
      grandTotal += subtotal;

      if (subtotalOutput) {
        subtotalOutput.textContent = new Intl.NumberFormat('en-KE', { style: 'currency', currency: 'KES' }).format(subtotal);
      }
    });

    const grandTotalEl = document.getElementById('order_grand_total');
    const totalQtyEl = document.getElementById('order_total_qty');
    const depositInput = document.getElementById('order_deposit_amount');

    if (grandTotalEl) grandTotalEl.textContent = new Intl.NumberFormat('en-KE', { style: 'currency', currency: 'KES' }).format(grandTotal);
    if (totalQtyEl) totalQtyEl.textContent = totalItems;
    
    // Suggest 50% deposit
    if (depositInput && (!depositInput.value || parseFloat(depositInput.value) === 0)) {
      depositInput.value = (grandTotal * 0.5).toFixed(2);
    }
  }

  if (addItemBtn && itemsContainer) {
    addItemBtn.addEventListener('click', function () {
      const index = document.querySelectorAll('.order-item-row').length;
      const newRow = document.createElement('div');
      newRow.className = 'row g-2 align-items-center mb-2 order-item-row border-bottom pb-2';
      newRow.innerHTML = `
        <div class="col-md-4">
          <input type="text" name="items[${index}][title]" class="form-control form-control-sm" placeholder="Garment (e.g. Blazer)" required>
        </div>
        <div class="col-md-2">
          <input type="text" name="items[${index}][size]" class="form-control form-control-sm" placeholder="Size (e.g. 34 / M)" required>
        </div>
        <div class="col-md-2">
          <input type="number" name="items[${index}][quantity]" class="form-control form-control-sm item-qty" value="1" min="1" required>
        </div>
        <div class="col-md-2">
          <input type="number" step="0.01" name="items[${index}][price]" class="form-control form-control-sm item-price" placeholder="Price" required>
        </div>
        <div class="col-md-1 text-end">
          <span class="small fw-bold item-subtotal">KSh 0</span>
        </div>
        <div class="col-md-1 text-center">
          <button type="button" class="btn btn-outline-danger btn-sm btn-remove-row"><i class="bi bi-trash"></i></button>
        </div>
      `;
      itemsContainer.appendChild(newRow);
      attachRowEvents(newRow);
    });

    function attachRowEvents(row) {
      const qtyInput = row.querySelector('.item-qty');
      const priceInput = row.querySelector('.item-price');
      const removeBtn = row.querySelector('.btn-remove-row');

      if (qtyInput) qtyInput.addEventListener('input', updateOrderTotals);
      if (priceInput) priceInput.addEventListener('input', updateOrderTotals);
      if (removeBtn) {
        removeBtn.addEventListener('click', function () {
          row.remove();
          updateOrderTotals();
        });
      }
    }

    // Attach to existing initial rows
    document.querySelectorAll('.order-item-row').forEach(attachRowEvents);
  }

  // ==========================================================
  // Generic Action Confirmation Modal Handler
  // ==========================================================
  document.addEventListener('click', function (e) {
    const trigger = e.target.closest('.btn-confirm-action, [data-confirm-modal]');
    if (!trigger) return;

    e.preventDefault();
    const modalEl = document.getElementById('actionConfirmModal');
    const msg = trigger.getAttribute('data-message') || 'Are you sure you want to proceed with this action?';
    const href = trigger.getAttribute('data-href') || trigger.getAttribute('href');
    const formId = trigger.getAttribute('data-form-id');

    if (!modalEl || typeof bootstrap === 'undefined') {
      if (confirm(msg)) {
        if (formId) {
          const form = document.getElementById(formId);
          if (form) form.submit();
        } else if (href) {
          window.location.href = href;
        }
      }
      return;
    }

    const title = trigger.getAttribute('data-title') || 'Confirm Action';
    const confirmText = trigger.getAttribute('data-confirm-text') || 'Yes, Proceed';
    const confirmClass = trigger.getAttribute('data-confirm-class') || 'btn-danger';

    const titleEl = document.getElementById('actionConfirmModalLabel');
    const msgEl = document.getElementById('actionConfirmMessage');
    const submitBtn = document.getElementById('actionConfirmSubmitBtn');

    if (titleEl) titleEl.textContent = title;
    if (msgEl) msgEl.textContent = msg;

    if (submitBtn) {
      submitBtn.textContent = confirmText;
      submitBtn.className = 'btn btn-sm px-3 fw-semibold ' + confirmClass;
      submitBtn.onclick = null;

      if (formId) {
        submitBtn.removeAttribute('href');
        submitBtn.onclick = function (evt) {
          evt.preventDefault();
          const targetForm = document.getElementById(formId);
          if (targetForm) targetForm.submit();
        };
      } else if (href) {
        submitBtn.setAttribute('href', href);
        submitBtn.onclick = null;
      }
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  });

  // ==========================================================
  // Staff / User Account Edit Modal Populator
  // ==========================================================
  document.addEventListener('click', function (e) {
    const editBtn = e.target.closest('.btn-edit-user');
    if (!editBtn) return;

    e.preventDefault();
    const modalEl = document.getElementById('editStaffModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;

    const id = editBtn.getAttribute('data-id') || '';
    const fullname = editBtn.getAttribute('data-fullname') || '';
    const username = editBtn.getAttribute('data-username') || '';
    const email = editBtn.getAttribute('data-email') || '';
    const role = editBtn.getAttribute('data-role') || 'staff';
    const phone = editBtn.getAttribute('data-phone') || '';
    const active = editBtn.getAttribute('data-active') === '1';
    const isSelf = editBtn.getAttribute('data-is-self') === '1';

    const idInput = document.getElementById('edit_user_id');
    const nameInput = document.getElementById('edit_full_name');
    const userInput = document.getElementById('edit_username');
    const emailInput = document.getElementById('edit_email');
    const phoneInput = document.getElementById('edit_phone');
    const passInput = document.getElementById('edit_password');
    const roleSelect = document.getElementById('edit_role');
    const activeCheck = document.getElementById('edit_is_active');
    const selfAlert = document.getElementById('edit_self_alert');

    if (idInput) idInput.value = id;
    if (nameInput) nameInput.value = fullname;
    if (userInput) userInput.value = username;
    if (emailInput) emailInput.value = email;
    if (phoneInput) phoneInput.value = phone;
    if (passInput) passInput.value = '';

    if (roleSelect) {
      roleSelect.value = role;
      if (isSelf) {
        roleSelect.setAttribute('disabled', 'disabled');
      } else {
        roleSelect.removeAttribute('disabled');
      }
    }

    if (activeCheck) {
      activeCheck.checked = active;
      if (isSelf) {
        activeCheck.setAttribute('disabled', 'disabled');
        activeCheck.checked = true;
      } else {
        activeCheck.removeAttribute('disabled');
      }
    }

    if (selfAlert) {
      if (isSelf) {
        selfAlert.classList.remove('d-none');
      } else {
        selfAlert.classList.add('d-none');
      }
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  });
});

