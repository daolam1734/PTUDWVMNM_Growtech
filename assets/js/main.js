// Falling blossoms effect
function initBlossoms() {
  console.log("GrowTech: Tet blossoms initialized");
  const createBlossom = () => {
    const blossom = document.createElement('div');
    blossom.className = 'blossom';
    const types = [
      `<svg viewBox="0 0 24 24" fill="#ffc107" width="100%" height="100%"><path d="M12,22C12,22 10,18 10,15C10,12 12,10 12,10C12,10 14,12 14,15C14,18 12,22 12,22M16.5,18.5C16.5,18.5 13.5,16.5 11.5,14.5C9.5,12.5 9.5,10 9.5,10C9.5,10 12,10 14,12C16,14 18,17 18,17L16.5,18.5M19,12C19,12 15,12 12,12C9,12 7,10 7,10C7,10 9,8 12,8C15,8 19,8 19,8V12M16.5,5.5L18,7C18,7 16,10 14,12C12,14 9.5,14 9.5,14C9.5,14 9.5,11.5 11.5,9.5C13.5,7.5 16.5,5.5 16.5,5.5M12,2C12,2 14,6 14,9C14,12 12,14 12,14C12,14 10,12 10,9C10,6 12,2 12,2M7.5,5.5C7.5,5.5 10.5,7.5 12.5,9.5C14.5,11.5 14.5,14 14.5,14C14.5,14 12,14 10,12C8,10 6,7 6,7L7.5,5.5M5,12C5,12 9,12 12,12C15,12 17,14 17,14C17,14 15,16 12,16C9,16 5,16 5,16V12M7.5,18.5L6,17C6,17 8,14 10,12C12,10 14.5,10 14.5,10C14.5,10 14.5,12.5 12.5,14.5C10.5,16.5 7.5,18.5 7.5,18.5Z"/></svg>`,
      `<svg viewBox="0 0 24 24" fill="#ff69b4" width="100%" height="100%"><path d="M12,22C12,22 10,18 10,15C10,12 12,10 12,10C12,10 14,12 14,15C14,18 12,22 12,22M16.5,18.5C16.5,18.5 13.5,16.5 11.5,14.5C9.5,12.5 9.5,10 9.5,10C9.5,10 12,10 14,12C16,14 18,17 18,17L16.5,18.5M19,12C19,12 15,12 12,12C9,12 7,10 7,10C7,10 9,8 12,8C15,8 19,8 19,8V12M16.5,5.5L18,7C18,7 16,10 14,12C12,14 9.5,14 9.5,14C9.5,14 9.5,11.5 11.5,9.5C13.5,7.5 16.5,5.5 16.5,5.5M12,2C12,2 14,6 14,9C14,12 12,14 12,14C12,14 10,12 10,9C10,6 12,2 12,2M7.5,5.5C7.5,5.5 10.5,7.5 12.5,9.5C14.5,11.5 14.5,14 14.5,14C14.5,14 12,14 10,12C8,10 6,7 6,7L7.5,5.5M5,12C5,12 9,12 12,12C15,12 17,14 17,14C17,14 15,16 12,16C9,16 5,16 5,16V12M7.5,18.5L6,17C6,17 8,14 10,12C12,10 14.5,10 14.5,10C14.5,10 14.5,12.5 12.5,14.5C10.5,16.5 7.5,18.5 7.5,18.5Z"/></svg>`,
      `<svg viewBox="0 0 24 24" width="100%" height="100%"><rect x="5" y="3" width="14" height="18" rx="2" fill="#d32f2f"/><path d="M12,10 L19,3 L5,3 Z" fill="#b71c1c"/><circle cx="12" cy="12" r="2" fill="#ffc107"/></svg>`,
      `<svg viewBox="0 0 24 24" fill="#ffc107" width="100%" height="100%"><path d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/></svg>`
    ];
    blossom.innerHTML = types[Math.floor(Math.random() * types.length)];

    const startLeft = Math.random() * 100;
    const size = (Math.random() * 15 + 20);
    blossom.style.left = startLeft + 'vw';
    blossom.style.width = size + 'px';
    blossom.style.height = size + 'px';
    blossom.style.position = 'fixed';
    blossom.style.top = '-50px';
    blossom.style.zIndex = '10000';
    blossom.style.pointerEvents = 'none';
    blossom.style.color = '#ffc107'; // Fallback color for stars

    const duration = Math.random() * 5 + 7;
    blossom.style.animation = `fall ${duration}s linear forwards`;

    // Ensure it's added to the very end of body
    document.body.appendChild(blossom);

    // Fallback removal
    setTimeout(() => {
      if (blossom.parentNode) blossom.remove();
    }, duration * 1000 + 1000);
  };

  // Initial batch
  for (let i = 0; i < 15; i++) {
    setTimeout(createBlossom, Math.random() * 3000);
  }
  setInterval(createBlossom, 600);
}

// Header interactions: search suggestions, cart hover, sticky shrink, mobile offcanvas
document.addEventListener('DOMContentLoaded', function () {
  initBlossoms();

  // Debounce helper
  function debounce(fn, delay) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), delay);
    };
  }

  // Search suggestions
  const input = document.getElementById('header-search-input');
  const suggestionsBox = document.getElementById('search-suggestions');
  let selected = -1;

  async function fetchSuggestions(q) {
    if (!q) { suggestionsBox.innerHTML = ''; suggestionsBox.classList.remove('show'); suggestionsBox.setAttribute('aria-hidden', 'true'); return; }
    try {
      const res = await fetch('/weblaptop/search_suggest.php?q=' + encodeURIComponent(q));
      const data = await res.json();
      renderSuggestions(data);
    } catch (e) {
      console.error(e);
    }
  }

  const debouncedFetch = debounce((e) => fetchSuggestions(e.target.value.trim()), 250);
  if (input) {
    input.addEventListener('input', debouncedFetch);
    input.addEventListener('keydown', function (e) {
      const items = suggestionsBox.querySelectorAll('.suggestion-item');
      if (!items.length) return;
      if (e.key === 'ArrowDown') { e.preventDefault(); selected = Math.min(selected + 1, items.length - 1); updateSelection(items); }
      if (e.key === 'ArrowUp') { e.preventDefault(); selected = Math.max(selected - 1, 0); updateSelection(items); }
      if (e.key === 'Enter') { e.preventDefault(); if (selected >= 0) items[selected].click(); else { this.form.submit(); } }
      if (e.key === 'Escape') { suggestionsBox.classList.remove('show'); suggestionsBox.setAttribute('aria-hidden', 'true'); }
    });
    document.addEventListener('click', function (ev) { if (!ev.target.closest('#header-search')) { suggestionsBox.classList.remove('show'); suggestionsBox.setAttribute('aria-hidden', 'true'); } });
  }

  function updateSelection(items) {
    items.forEach((it, idx) => it.classList.toggle('active', idx === selected));
    if (items[selected]) items[selected].scrollIntoView({ block: 'nearest' });
  }

  function renderSuggestions(list) {
    if (!list || !list.length) { suggestionsBox.innerHTML = '<div class="p-2 text-muted">Không có kết quả</div>'; suggestionsBox.classList.add('show'); suggestionsBox.setAttribute('aria-hidden', 'false'); return; }
    selected = -1;
    suggestionsBox.innerHTML = list.map(it => `
      <a href="/weblaptop/product.php?id=${it.id}" class="d-flex suggestion-item p-2 align-items-center" role="option">
        <img src="${it.image || 'https://placehold.co/80x60?text=No+Image'}" width="60" height="45" class="me-2" alt="">
        <div class="flex-fill">
          <div class="small text-muted">${it.brand || ''} <span class="mx-1">•</span> ${it.sku}</div>
          <div class="fw-semibold">${it.name}</div>
          <div class="small text-primary">${Number(it.price).toLocaleString('vi-VN')} VNĐ</div>
        </div>
      </a>
    `).join('');
    suggestionsBox.classList.add('show');
    suggestionsBox.setAttribute('aria-hidden', 'false');
    suggestionsBox.querySelectorAll('.suggestion-item').forEach(el => el.addEventListener('click', () => { suggestionsBox.classList.remove('show'); suggestionsBox.setAttribute('aria-hidden', 'true'); }));
  }

  // Cart dropdown show/hide
  const cartBtn = document.getElementById('header-cart-btn');
  const cartDropdown = document.getElementById('header-cart-dropdown');
  if (cartBtn && cartDropdown) {
    let cartTimeout;
    cartBtn.addEventListener('mouseenter', () => { clearTimeout(cartTimeout); cartDropdown.classList.add('show'); cartDropdown.setAttribute('aria-hidden', 'false'); cartBtn.setAttribute('aria-expanded', 'true'); });
    cartBtn.addEventListener('mouseleave', () => { cartTimeout = setTimeout(() => { cartDropdown.classList.remove('show'); cartDropdown.setAttribute('aria-hidden', 'true'); cartBtn.setAttribute('aria-expanded', 'false'); }, 250); });
    cartDropdown.addEventListener('mouseenter', () => { clearTimeout(cartTimeout); cartDropdown.classList.add('show'); cartDropdown.setAttribute('aria-hidden', 'false'); cartBtn.setAttribute('aria-expanded', 'true'); });
    cartDropdown.addEventListener('mouseleave', () => { cartDropdown.classList.remove('show'); cartDropdown.setAttribute('aria-hidden', 'true'); cartBtn.setAttribute('aria-expanded', 'false'); });
  }

  // Dropdown accessible toggles for nav
  document.querySelectorAll('.nav-dropdown > button').forEach(btn => {
    const menu = document.getElementById(btn.getAttribute('aria-controls')) || btn.nextElementSibling;
    btn.addEventListener('click', (e) => {
      const open = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!open));
      if (menu) menu.style.display = open ? 'none' : 'block';
    });
    btn.addEventListener('keydown', (e) => { if (e.key === 'Escape') { btn.setAttribute('aria-expanded', 'false'); if (menu) menu.style.display = 'none'; btn.focus(); } });
  });

  // Mobile offcanvas toggling
  const mobileToggle = document.getElementById('mobile-menu-toggle');
  const mobileMenu = document.getElementById('mobileMenu');
  const mobileClose = document.getElementById('mobileMenuClose');
  if (mobileToggle && mobileMenu) {
    mobileToggle.addEventListener('click', () => { mobileMenu.setAttribute('aria-hidden', 'false'); mobileToggle.setAttribute('aria-expanded', 'true'); document.body.style.overflow = 'hidden'; });
    if (mobileClose) mobileClose.addEventListener('click', () => { mobileMenu.setAttribute('aria-hidden', 'true'); mobileToggle.setAttribute('aria-expanded', 'false'); document.body.style.overflow = ''; });
    // close on outside click
    mobileMenu.addEventListener('click', (e) => { if (e.target === mobileMenu) { mobileMenu.setAttribute('aria-hidden', 'true'); mobileToggle.setAttribute('aria-expanded', 'false'); document.body.style.overflow = ''; } });
  }

  // Sticky shrink header on scroll
  const header = document.querySelector('.tet-header');
  let lastScroll = 0;
  if (header) window.addEventListener('scroll', () => {
    const sc = window.scrollY;
    if (sc > 100) header.classList.add('shrink');
    else header.classList.remove('shrink');
    lastScroll = sc;
  });

  // Auto-dismiss flash alerts
  document.querySelectorAll('.flash-alert').forEach(function (el) {
    setTimeout(function () {
      el.classList.add('fade');
      setTimeout(() => { try { el.remove(); } catch (e) { } }, 300);
    }, 5000);
  });

  // Back to Top Button
  const backToTop = document.createElement('div');
  backToTop.id = 'back-to-top';
  backToTop.innerHTML = '<i class="bi bi-arrow-up"></i>';
  document.body.appendChild(backToTop);

  window.addEventListener('scroll', () => {
    if (window.scrollY > 300) backToTop.classList.add('show');
    else backToTop.classList.remove('show');
  });

  backToTop.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  // Scroll Container Navigation (Prev/Next buttons)
  const scrollContainers = document.querySelectorAll('.scroll-container');
  console.log("GrowTech: Found " + scrollContainers.length + " scroll containers");

  scrollContainers.forEach((container, index) => {
    const wrapper = container.closest('.scroll-wrapper');
    if (!wrapper) return;

    const btnLeft = wrapper.querySelector('.scroll-btn-left');
    const btnRight = wrapper.querySelector('.scroll-btn-right');

    const getScrollAmount = () => {
      const item = container.querySelector('.scroll-item');
      return item ? (item.offsetWidth + 15) : 300;
    };

    let isHovered = false;
    wrapper.addEventListener('mouseenter', () => isHovered = true);
    wrapper.addEventListener('mouseleave', () => isHovered = false);

    const isScrollingNeeded = () => {
      return container.children.length > 4 || container.scrollWidth > container.clientWidth;
    };

    const setupInfiniteScroll = () => {
      if (container.dataset.cloned === "true") return true;
      if (isScrollingNeeded()) {
        const items = Array.from(container.children);
        items.forEach(item => {
          const clone = item.cloneNode(true);
          container.appendChild(clone);
        });
        container.dataset.cloned = "true";
        if (btnLeft) btnLeft.style.display = 'flex';
        if (btnRight) btnRight.style.display = 'flex';
        return true;
      }
      if (btnLeft) btnLeft.style.display = 'none';
      if (btnRight) btnRight.style.display = 'none';
      container.style.justifyContent = 'center';
      return false;
    };

    let scrollingActive = false;
    setTimeout(() => { scrollingActive = setupInfiniteScroll(); }, 1000);

    const handleSeamlessJump = () => {
      const halfWidth = container.scrollWidth / 2;
      if (container.scrollLeft >= halfWidth - 5) {
        container.style.scrollBehavior = 'auto';
        container.scrollLeft -= halfWidth;
        container.style.scrollBehavior = 'smooth';
      } else if (container.scrollLeft <= 0) {
        container.style.scrollBehavior = 'auto';
        container.scrollLeft += halfWidth;
        container.style.scrollBehavior = 'smooth';
      }
    };

    if (btnLeft) {
      btnLeft.addEventListener('click', (e) => {
        e.preventDefault();
        const halfWidth = container.scrollWidth / 2;
        if (container.scrollLeft <= 5) {
          container.style.scrollBehavior = 'auto';
          container.scrollLeft = halfWidth;
          container.style.scrollBehavior = 'smooth';
        }
        container.scrollLeft -= getScrollAmount();
      });
    }

    if (btnRight) {
      btnRight.addEventListener('click', (e) => {
        e.preventDefault();
        container.scrollLeft += getScrollAmount();
        setTimeout(handleSeamlessJump, 600);
      });
    }

    setInterval(() => {
      if (scrollingActive && !isHovered) {
        container.scrollLeft += getScrollAmount();
        setTimeout(handleSeamlessJump, 600);
      }
    }, 3000 + (index * 500));

    window.addEventListener('resize', () => {
      if (isScrollingNeeded() && !scrollingActive) scrollingActive = setupInfiniteScroll();
    });
  });

  // Flash Sale Timer
  const flashSaleContainer = document.querySelector('.flash-sale-container');
  const timerH = document.getElementById('timer-h');
  const timerM = document.getElementById('timer-m');
  const timerS = document.getElementById('timer-s');

  if (flashSaleContainer && timerH && timerM && timerS) {
    const endTimeStr = flashSaleContainer.getAttribute('data-end-time');
    if (endTimeStr) {
      const endTime = new Date(endTimeStr).getTime();

      const updateTimer = () => {
        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance < 0) {
          timerH.textContent = '00';
          timerM.textContent = '00';
          timerS.textContent = '00';
          return;
        }

        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        timerH.textContent = hours.toString().padStart(2, '0');
        timerM.textContent = minutes.toString().padStart(2, '0');
        timerS.textContent = seconds.toString().padStart(2, '0');
      };

      updateTimer();
      setInterval(updateTimer, 1000);
    }
  }
});