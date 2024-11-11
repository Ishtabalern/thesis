 // JavaScript to toggle active tab content and store selection in localStorage
 document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.btn-tabs');
    const contents = document.querySelectorAll('.tab-content');

    // Get the last active tab from localStorage or default to 'home'
    const activeTab = localStorage.getItem('activeTab') || 'home';
    document.getElementById(activeTab).classList.add('active');

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