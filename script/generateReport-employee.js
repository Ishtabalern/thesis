document.querySelectorAll('.buttons').forEach((dropdown) => {
   dropdown.addEventListener('click', (event) => {
     event.stopPropagation();
     const dropdownContent = dropdown.nextElementSibling;
     dropdownContent.classList.toggle('show');
   });
 });