import { chart } from './utils/chart.js';

// Restore state from localStorage
const savedClient = localStorage.getItem('selectedClient') || 'client-one';
const savedCategory = localStorage.getItem('selectedCategory') || 'sales';

if (document.getElementById(savedClient)) {
   document.getElementById(savedClient).classList.add('active');
 }


// Dropdown Toggle
document.querySelectorAll('.buttons').forEach((dropdown) => {
  dropdown.addEventListener('click', (event) => {
    event.stopPropagation();
    const dropdownContent = dropdown.nextElementSibling;
    dropdownContent.classList.toggle('show');
  });
});

// Client Selection
document.querySelectorAll('.btn-client').forEach((btn) => {
   btn.addEventListener('click', (event) => {
       event.stopPropagation();

       const clientId = btn.getAttribute('data-id');
       const clientName = btn.textContent.trim();
       const labelClient = document.querySelector('.label-client');

       // Update active tab
       document.querySelectorAll('.tab-content').forEach((tab) => {
           tab.classList.remove('active');
       });
       const clientTab = document.getElementById(clientId);
       if (clientTab) clientTab.classList.add('active');

       // Update client label
       if (labelClient) labelClient.textContent = clientName;

       // Show category dropdown
       const categoryDropdown = document.querySelector('.category');
       if (categoryDropdown) categoryDropdown.style.display = 'block';

       // Set default category (Sales)
       document.querySelectorAll('.sales-container').forEach((container) => {
           container.classList.remove('active');
       });
       const salesContainer = document.querySelector(`#${clientId} #sales`);
       if (salesContainer) salesContainer.classList.add('active');

       const labelCategory = document.querySelector('.label-category');
       if (labelCategory) labelCategory.textContent = 'Sales';

       // Save to localStorage
       localStorage.setItem('selectedClient', clientId);
       localStorage.setItem('selectedCategory', 'sales');

       // Initialize chart
       initializeChart(clientId, 'sales');
   });
});


// Category Selection
document.querySelectorAll('.btn-category').forEach((btn) => {
   btn.addEventListener('click', (event) => {
       event.stopPropagation();

       const categoryId = btn.getAttribute('data-category');
       const categoryName = btn.textContent.trim();
       const labelCategory = document.querySelector('.label-category');
       const activeClient = document.querySelector('.tab-content.active');

       if (activeClient) {
           const activeClientId = activeClient.id;

           // Update active category
           document.querySelectorAll('.sales-container').forEach((container) => {
               container.classList.remove('active');
           });
           const categoryContainer = document.querySelector(`#${activeClientId} #${categoryId}`);
           if (categoryContainer) categoryContainer.classList.add('active');

           // Update category label
           if (labelCategory) labelCategory.textContent = categoryName;

           // Save to localStorage
           localStorage.setItem('selectedCategory', categoryId);

           // Initialize chart
           initializeChart(activeClientId, categoryId);
       }
   });
});

document.addEventListener('DOMContentLoaded', () => {
   document.querySelectorAll('.btn-client').forEach((btn) => {
     btn.addEventListener('click', () => {
       const clientId = btn.getAttribute('data-id');
       const categoryId = 'sales'; // Example category
       initializeChart(clientId, categoryId);
     });
   });
 });



// generate-report.js (Modify this function)
function initializeChart(clientId, categoryId) {
   const chartId = `chart-${clientId}-${categoryId}`;
   const canvas = document.getElementById(chartId);
 
   if (canvas) {
     const container = canvas.closest('.sales-container');
 
     // Make the container visible
     if (container) {
       container.classList.add('active');
     }
 
     // Render chart after container becomes visible
     setTimeout(() => {
       chart(canvas, {
         labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
         data: [150, 200, 250, 300, 350],
         label: `${categoryId} Data for ${clientId}`,
       });
     }, 300);
   }
 }

// Restore state on page load
if (savedClient && savedCategory) {
   const activeClientTab = document.getElementById(savedClient);
   const labelClient = document.querySelector('.label-client');
   const savedClientButton = document.querySelector(`.btn-client[data-id="${savedClient}"]`);
   const activeCategory = document.querySelector(`#${savedClient} #${savedCategory}`);
   const labelCategory = document.querySelector('.label-category');
   const savedCategoryButton = document.querySelector(`.btn-category[data-category="${savedCategory}"]`);

   if (activeClientTab) activeClientTab.classList.add('active');
   if (labelClient && savedClientButton) labelClient.textContent = savedClientButton.textContent.trim();
   if (document.querySelector('.category')) document.querySelector('.category').style.display = 'block';
   if (activeCategory) activeCategory.classList.add('active');
   if (labelCategory && savedCategoryButton) labelCategory.textContent = savedCategoryButton.textContent.trim();

   initializeChart(savedClient, savedCategory);
}