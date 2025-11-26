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

// Select all elements you want to animate
const fadeEls = document.querySelectorAll('.fade-in');

const observer = new IntersectionObserver((entries, observer) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      observer.unobserve(entry.target); // animate only once
    }
  });
}, { threshold: 0.8 });

fadeEls.forEach(el => observer.observe(el));

//webdevpage

window.addEventListener('scroll', function() {
  const navbar = document.querySelector('.navbar-main');
  if (window.scrollY > 50) {
    navbar.classList.add('scrolled');
  } else {
    navbar.classList.remove('scrolled');
  }
});
