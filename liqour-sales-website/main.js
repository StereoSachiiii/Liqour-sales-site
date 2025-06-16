const slides = document.querySelectorAll(".slide");
const sliderNav = document.querySelector(".slider-nav");
const slider = document.querySelector(".slider");
const nextBtn = document.querySelector(".next");
const prevBtn = document.querySelector(".prev");

let currentSlide = 0;
let slideCount = slides.length;
const dots = [];

slides.forEach((_, index) => {
  const dot = document.createElement("div");
  dot.classList.add("nav-dot");
  if (index === 0) dot.classList.add("active");
  dot.addEventListener("click", () => {
    goToSlide(index);
  });
  sliderNav.appendChild(dot);
  dots.push(dot);
});

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

prevBtn.addEventListener("click", prevSlide);
nextBtn.addEventListener("click", nextSlide);

setInterval(nextSlide,5000);
