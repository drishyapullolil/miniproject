document.addEventListener("DOMContentLoaded", () => {
    const images = document.querySelectorAll(".image-grid img");
  
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("scrolled-in");
        }
      });
    }, {
      threshold: 0.2, // Trigger when 20% of the image is visible
    });
  
    images.forEach((image) => observer.observe(image));
  });
  