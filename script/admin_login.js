function toggleDropdown() {
   const dropdown = document.getElementById('dropdownContent');
   dropdown.classList.toggle('show');
   }   

// Close the dropdown when clicking outside
window.onclick = function(event) {
   if (!event.target.matches('.dropbtn')) { 
       const dropdowns = document.getElementsByClassName('dropdown-content');
       for (let i = 0; i < dropdowns.length; i++) {
           if (dropdowns[i].classList.contains('show')) {
               dropdowns[i].classList.remove('show');
           }
       }
   }
}