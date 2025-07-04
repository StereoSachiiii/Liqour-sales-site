const slides = document.querySelectorAll(".slide");
const sliderNav = document.querySelector(".slider-nav");
const slider = document.querySelector(".slider");
const nextBtn = document.querySelector(".next");
const prevBtn = document.querySelector(".prev");


const orderBtn = document.querySelector(".order-btn");

let currentSlide = 0;
let slideCount = slides.length;
const dots = [];
const cart = [];

slides.forEach((_, index) => {
  const dot = document.createElement("div");
  dot.classList.add("nav-dot");
  if (index === 0) dot.classList.add("active");
  dot.addEventListener("click", () => goToSlide(index));
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
setInterval(nextSlide, 8000);

const newArrivals = document.querySelector(".new-arrivals");

whatsNew.forEach((item) => {
  const newItem = document.createElement("div");
  newItem.classList.add("new-item");

  const newItemImageContainer = document.createElement("div");
  newItemImageContainer.classList.add("image-container");
  newItemImageContainer.style.backgroundImage = `url(${item.image})`;
  newItemImageContainer.style.backgroundSize = "cover";
  newItemImageContainer.style.backgroundPosition = "center";

  const description = document.createElement("div");
  description.classList.add("description");
  description.innerText = item.name;

  const priceAddToCartContainer = document.createElement("div");
  priceAddToCartContainer.classList.add("price-add-to-cart");

  const quantityInput = document.createElement("input");
  quantityInput.setAttribute("type", "number");
  quantityInput.setAttribute("min", "1");
  quantityInput.setAttribute("value", "1");
  quantityInput.classList.add("quantity-input");

  const priceElement = document.createElement("span");
  priceElement.classList.add("product-price");
  priceElement.innerText = item.price;

  const addToCartBtn = document.createElement("button");
  addToCartBtn.classList.add("add-to-cart-btn");
  addToCartBtn.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
    </svg>`;

  addToCartBtn.setAttribute("data-product-name", item.name);
  addToCartBtn.setAttribute("data-product-price", item.price);

  addToCartBtn.addEventListener("click", () => {
    const productName = addToCartBtn.dataset.productName;
    const productPrice = addToCartBtn.dataset.productPrice;
    const quantity = parseInt(quantityInput.value);

    const productExists = cart.some((item) => {
      if (item.productName === productName) {
        item.quantity += quantity;
        return true;
      }
      return false;
    });

    if (!productExists) {
      cart.push({
        productName,
        productPrice,
        quantity
      });
    }
    function addedAnimation() {

  const added = document.createElement("span");
  added.classList.add("add-animation");
  added.innerText=`Item added!!`;
  priceAddToCartContainer.appendChild(added);

  

}
    addedAnimation();
    console.log(cart);
  });

const slides = document.querySelectorAll(".slide");
const sliderNav = document.querySelector(".slider-nav");
const slider = document.querySelector(".slider");
const nextBtn = document.querySelector(".next");
const prevBtn = document.querySelector(".prev");


const orderBtn = document.querySelector(".order-btn");

let currentSlide = 0;
let slideCount = slides.length;
const dots = [];
const cart = [];

slides.forEach((_, index) => {
  const dot = document.createElement("div");
  dot.classList.add("nav-dot");
  if (index === 0) dot.classList.add("active");
  dot.addEventListener("click", () => goToSlide(index));
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
setInterval(nextSlide, 8000);

const newArrivals = document.querySelector(".new-arrivals");

whatsNew.forEach((item) => {
  const newItem = document.createElement("div");
  newItem.classList.add("new-item");

  const newItemImageContainer = document.createElement("div");
  newItemImageContainer.classList.add("image-container");
  newItemImageContainer.style.backgroundImage = `url(${item.image})`;
  newItemImageContainer.style.backgroundSize = "cover";
  newItemImageContainer.style.backgroundPosition = "center";

  const description = document.createElement("div");
  description.classList.add("description");
  description.innerText = item.name;

  const priceAddToCartContainer = document.createElement("div");
  priceAddToCartContainer.classList.add("price-add-to-cart");

  const quantityInput = document.createElement("input");
  quantityInput.setAttribute("type", "number");
  quantityInput.setAttribute("min", "1");
  quantityInput.setAttribute("value", "1");
  quantityInput.classList.add("quantity-input");

  const priceElement = document.createElement("span");
  priceElement.classList.add("product-price");
  priceElement.innerText = item.price;

  const addToCartBtn = document.createElement("button");
  addToCartBtn.classList.add("add-to-cart-btn");
  addToCartBtn.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
    </svg>`;

  addToCartBtn.setAttribute("data-product-name", item.name);
  addToCartBtn.setAttribute("data-product-price", item.price);

  addToCartBtn.addEventListener("click", () => {
    const productName = addToCartBtn.dataset.productName;
    const productPrice = addToCartBtn.dataset.productPrice;
    const quantity = parseInt(quantityInput.value);

    const productExists = cart.some((item) => {
      if (item.productName === productName) {
        item.quantity += quantity;
        return true;
      }
      return false;
    });

    if (!productExists) {
      cart.push({
        productName,
        productPrice,
        quantity
      });
    }
    function addedAnimation() {

  const added = document.createElement("span");
  added.classList.add("add-animation");
  added.innerText=`Item added!!`;
  priceAddToCartContainer.appendChild(added);

  

}
    addedAnimation();
    console.log(cart);
  });

orderBtn.addEventListener('click', async () => {
  let response = await fetch('cart.php', {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ cart }) 
  });

  const result = await response.json(); 
  console.log(result);
});




  priceAddToCartContainer.appendChild(priceElement);
  priceAddToCartContainer.appendChild(quantityInput);
  priceAddToCartContainer.appendChild(addToCartBtn);

  newItem.appendChild(newItemImageContainer);
  newItem.appendChild(description);
  newItem.appendChild(priceAddToCartContainer);

  newArrivals.appendChild(newItem);
});




  priceAddToCartContainer.appendChild(priceElement);
  priceAddToCartContainer.appendChild(quantityInput);
  priceAddToCartContainer.appendChild(addToCartBtn);

  newItem.appendChild(newItemImageContainer);
  newItem.appendChild(description);
  newItem.appendChild(priceAddToCartContainer);

  newArrivals.appendChild(newItem);
});
