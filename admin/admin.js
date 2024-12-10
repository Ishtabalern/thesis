document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.btn-tabs');
    const contents = document.querySelectorAll('.tab-content');

    // Get the last active tab from localStorage or default to 'home'
    const activeTab = localStorage.getItem('activeTab') || 'home';
    const activeElement = document.getElementById(activeTab);
    
    if (activeElement) {
        activeElement.classList.add('active');
    }

    tabs.forEach(tabButton => {
        tabButton.addEventListener('click', () => {
            const targetTab = tabButton.getAttribute('data-tab');

            // Remove 'active' class from all content sections
            contents.forEach(content => content.classList.remove('active'));

            // Add 'active' class to the target content section
            document.getElementById(targetTab).classList.add('active');

            // Store the currently active tab in localStorage
            localStorage.setItem('activeTab', targetTab);
        });
    });
});


/*manage user tablink

document.querySelectorAll('.active-account').forEach((users) => {
    users.addEventListener('click', () => {
        let target = users.getAttribute('data-account');
        let tables = document.querySelectorAll('.account-table')

        tables.forEach((content) =>{
            content.classList.remove('active');
        });

        document.getElementById(target).classList.add('active');
    })
})*/

// JavaScript
document.querySelectorAll('.active-account').forEach((users) => {
    users.addEventListener('click', () => {
        // Get the target id from data attribute
        let target = users.querySelector('input').getAttribute('data-account');

        // Remove 'active' class from all tables
        document.querySelectorAll('.account-table').forEach((content) => {
            content.classList.remove('active');
        });

        // Add 'active' class to the selected table
        document.getElementById(target).classList.add('active');
    });
});



//search bar
  // Get references to elements
  const searchBar = document.getElementById('search-bar');
  const tableData = document.querySelectorAll('.table-data tr');

  // Add event listener for search functionality
  searchBar.addEventListener('keyup', () => {
      const searchTerm = searchBar.value.toLowerCase();

      tableData.forEach(row => {
          const name = row.querySelector('.data-employee-name p').textContent.toLowerCase();
          
          if (name.includes(searchTerm)) {
              row.style.display = '';
          } else {
              row.style.display = 'none';
          }
      });
  });


  //scanner

  document.querySelector('.scan-btn').addEventListener('click', () => {
    fetch('http://192.168.1.2:5000/run-script', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Script ran successfully:\n' + data.output);
        } else {
            alert('Error running script:\n' + data.error);
        }
    })
    .catch(error => {
        alert('Failed to connect to the server:\n' + error);
    });
});