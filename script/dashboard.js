// Get modal, button, and overlay
var modal = document.getElementById("newModal");
var btn = document.getElementById("myBtn");
var overlay = document.querySelector(".modal-overlay");
var closeBtn = document.getElementsByClassName("close")[0];

// Open modal when clicking the button
btn.onclick = function() {
  modal.style.display = "block";
  overlay.style.display = "block"; // Show overlay
};

// Close modal when clicking the "X" button
closeBtn.onclick = function() {
  modal.style.display = "none";
  overlay.style.display = "none"; // Hide overlay
};

// Close modal when clicking outside (on overlay)
overlay.onclick = function() {
  modal.style.display = "none";
  overlay.style.display = "none";
};
