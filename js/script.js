window.addEventListener("scroll", function () {
  const navbar = document.getElementById("navbar");
  const btn = document.getElementById("signInBtn");

  if (window.scrollY > 50) {
    navbar.classList.add("scrolled");
    btn.classList.remove("btn-outline-light");
    btn.classList.add("btn-scroll-solid");
  } else {
    navbar.classList.remove("scrolled");
    btn.classList.add("btn-outline-light");
    btn.classList.remove("btn-scroll-solid");
  }
});

// improved fade-in observer (paste into js/script.js)
document.addEventListener('DOMContentLoaded', () => {
  const fadeEls = document.querySelectorAll('.fade-in');

  // Helper: immediate check for elements already visible
  const isInViewport = (el, offset = 0) => {
    const rect = el.getBoundingClientRect();
    return rect.top <= (window.innerHeight || document.documentElement.clientHeight) - offset;
  };

  // If browser supports IntersectionObserver
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target); // animate once
        }
      });
    }, {
      threshold: 0.12,                  // low threshold works better on small screens
      rootMargin: '0px 0px -10% 0px'    // trigger slightly before fully in view
    });

    fadeEls.forEach(el => {
      // if already mostly visible on load, mark visible right away
      if (isInViewport(el, 40)) {
        el.classList.add('visible');
      } else {
        observer.observe(el);
      }
    });

  } else {
    // fallback: if not supported, show everything
    fadeEls.forEach(el => el.classList.add('visible'));
  }

  // If your about-section (or others) sits inside Bootstrap tabs,
  // re-check when a tab is shown so animations trigger when revealed.
  document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', (e) => {
      // small delay to let Bootstrap finish showing content
      setTimeout(() => {
        fadeEls.forEach(el => {
          if (!el.classList.contains('visible') && isInViewport(el, 40)) {
            el.classList.add('visible');
          }
        });
      }, 80);
    });
  });
});


//webdevpage

window.addEventListener('scroll', function() {
  const navbar = document.querySelector('.navbar-main');
  if (window.scrollY > 50) {
    navbar.classList.add('scrolled');
  } else {
    navbar.classList.remove('scrolled');
  }
});
