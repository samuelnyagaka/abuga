/**
 * StitchCraft Masters - Public JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  // Bootstrap 5 client-side form validation
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });

  // Dynamic Portfolio Category Filtering
  const filterBtns = document.querySelectorAll('.gallery-filter-btn');
  const galleryItems = document.querySelectorAll('.gallery-item-col');

  if (filterBtns.length > 0 && galleryItems.length > 0) {
    filterBtns.forEach(btn => {
      btn.addEventListener('click', function () {
        filterBtns.forEach(b => b.classList.remove('active', 'btn-primary'));
        filterBtns.forEach(b => b.classList.add('btn-outline-secondary'));
        
        this.classList.add('active', 'btn-primary');
        this.classList.remove('btn-outline-secondary');

        const category = this.getAttribute('data-filter');

        galleryItems.forEach(item => {
          if (category === 'all' || item.getAttribute('data-category') === category) {
            item.style.display = 'block';
          } else {
            item.style.display = 'none';
          }
        });
      });
    });
  }

  // Lightbox Modal for Gallery Items
  const lightboxModal = document.getElementById('lightboxModal');
  if (lightboxModal) {
    lightboxModal.addEventListener('show.bs.modal', function (event) {
      const trigger = event.relatedTarget;
      const imgSrc = trigger.getAttribute('data-img');
      const title = trigger.getAttribute('data-title');
      const desc = trigger.getAttribute('data-desc');

      const modalImg = lightboxModal.querySelector('#lightboxImage');
      const modalTitle = lightboxModal.querySelector('#lightboxTitle');
      const modalDesc = lightboxModal.querySelector('#lightboxDesc');

      if (modalImg) modalImg.src = imgSrc;
      if (modalTitle) modalTitle.textContent = title;
      if (modalDesc) modalDesc.textContent = desc;
    });
  }

  // Bulk Uniform Quote Calculator Estimator (instant feedback for schools/institutions)
  const bulkQtyInput = document.getElementById('calc_quantity');
  const garmentTypeSelect = document.getElementById('calc_garment_type');
  const estimateOutput = document.getElementById('calc_estimate_output');

  function calculateEstimate() {
    if (!bulkQtyInput || !garmentTypeSelect || !estimateOutput) return;

    const qty = parseInt(bulkQtyInput.value, 10) || 0;
    const ratePerUnit = parseFloat(garmentTypeSelect.options[garmentTypeSelect.selectedIndex]?.getAttribute('data-base-price') || 0);

    if (qty <= 0 || ratePerUnit <= 0) {
      estimateOutput.textContent = 'Enter quantity & select uniform type';
      return;
    }

    // Tiered bulk discounts
    let discount = 0;
    if (qty >= 500) {
      discount = 0.15; // 15% discount for 500+
    } else if (qty >= 200) {
      discount = 0.10; // 10% discount for 200-499
    } else if (qty >= 50) {
      discount = 0.05; // 5% discount for 50-199
    }

    const subtotal = qty * ratePerUnit;
    const total = subtotal * (1 - discount);
    const formatted = new Intl.NumberFormat('en-KE', { style: 'currency', currency: 'KES', maximumFractionDigits: 0 }).format(total);
    const savings = discount > 0 ? ` (Includes ${(discount * 100)}% bulk tier savings)` : '';

    estimateOutput.textContent = `${formatted}${savings}`;
  }

  if (bulkQtyInput && garmentTypeSelect) {
    bulkQtyInput.addEventListener('input', calculateEstimate);
    garmentTypeSelect.addEventListener('change', calculateEstimate);
  }
});
