// Slider-only main.js
const slides = document.querySelectorAll(".slide");
const sliderNav = document.querySelector(".slider-nav");
const slider = document.querySelector(".slider");
const nextBtn = document.querySelector(".next");
const prevBtn = document.querySelector(".prev");

let currentSlide = 0;
let slideCount = slides.length;
const dots = [];

// Initialize slider only if elements exist
if (slider && slides.length > 0 && sliderNav) {
  
  // Use existing dots from HTML or create them if needed
  const existingDots = sliderNav.querySelectorAll(".nav-dot");
  
  if (existingDots.length > 0) {
    // Use existing dots from HTML
    existingDots.forEach((dot, index) => {
      dot.addEventListener("click", () => goToSlide(index));
      dots.push(dot);
    });
  } else {
    // Create dots if they don't exist
    slides.forEach((_, index) => {
      const dot = document.createElement("div");
      dot.classList.add("nav-dot");
      if (index === 0) dot.classList.add("active");
      dot.addEventListener("click", () => goToSlide(index));
      sliderNav.appendChild(dot);
      dots.push(dot);
    });
  }

  function updateDots() {
    dots.forEach((dot, index) => {
      dot.classList.toggle("active", index === currentSlide);
    });
  }

  function goToSlide(index) {
    currentSlide = (index + slideCount) % slideCount;
    slider.style.transform = `translateX(-${currentSlide * 100}%)`;
    updateDots();
  }

  function nextSlide() {
    goToSlide(currentSlide + 1);
  }

  function prevSlide() {
    goToSlide(currentSlide - 1);
  }

  // Event listeners for navigation buttons
  if (prevBtn) prevBtn.addEventListener("click", prevSlide);
  if (nextBtn) nextBtn.addEventListener("click", nextSlide);

  // Auto-play slider every 8 seconds
  setInterval(nextSlide, 8000);
}