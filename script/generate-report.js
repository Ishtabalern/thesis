import { chart } from './utils/chart.js';

chart();

// Dropdown Toggle
document.querySelectorAll('.buttons').forEach((dropdown) => {
  dropdown.addEventListener('click', (event) => {
    event.stopPropagation();
    const dropdownContent = dropdown.nextElementSibling;
    dropdownContent.classList.toggle('show');
  });
});


document.querySelectorAll('.btn-client')
  .forEach((buttons) => {
    buttons.addEventListener('click', () => {
      const btns = buttons.getAttribute('data-id');

      document.querySelectorAll('.client-content')
       .forEach((client) => {
        client.classList.remove('active');
       })

       document.getElementById(btns).classList.add('active');
    })
  })


