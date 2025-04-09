<?php
// Handle form submission before any HTML output
$submissionResult = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $servername = "localhost";
    $username = "admin";
    $password = "123";
    $dbname = "cskdb";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        $submissionResult = ["success" => false, "message" => "Connection failed: " . $conn->connect_error];
    } else {
        $newClient = trim($_POST['customer-name'] ?? '');

        if (!empty($newClient)) {
            $stmt = $conn->prepare("INSERT INTO clients (name) VALUES (?)");
            $stmt->bind_param("s", $newClient);

            if ($stmt->execute()) {
                $submissionResult = ["success" => true, "message" => "Client added successfully!"];
            } else {
                $submissionResult = ["success" => false, "message" => "Failed to add client."];
            }

            $stmt->close();
        } else {
            $submissionResult = ["success" => false, "message" => "Client name cannot be empty."];
        }

        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Client Form</title>
  <link rel="stylesheet" href="clients/client.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
</head>
<body>

<?php if ($submissionResult): ?>
  <div style="padding: 1em; background: <?= $submissionResult['success'] ? '#d4edda' : '#f8d7da' ?>; color: <?= $submissionResult['success'] ? '#155724' : '#721c24' ?>; margin: 1em;">
    <?= $submissionResult['message'] ?>
  </div>
<?php endif; ?>

<main class="content">
  <section id="client" class="tab-content">
    <div class="forms-container 1">
      <div class="forms-content">
        <header>
          <h2>Personal information</h2>
          <span>1 of 4</span>
        </header>

        <form action="" method="POST">
          <div class="name-information">
            <div class="info">
              <label for="first-name">First name</label>
              <input type="text" id="first-name" name="first-name" required>
            </div>
            <div class="info">
              <label for="Middle-name">Middle name</label>
              <input type="text" id="Middle-name" name="Middle-name" required>
            </div>
            <div class="info">
              <label for="Last-name">Last name</label>
              <input type="text" id="Last-name" name="Last-name" required>
            </div>
            <div class="info">
              <label for="Suffix">Suffix</label>
              <input type="text" id="Suffix" name="Suffix" required>
            </div>
          </div>

          <div class="customer-information">
            <div class="info">
              <label for="customer-name">Customer name</label>
              <input type="text" id="customer-name" name="customer-name" required>
            </div>
            <div class="info">
              <label for="company-name">Company name</label>
              <input type="text" id="company-name" name="company-name" required>
            </div>
            <div class="info">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" required>
            </div>
            <div class="info">
              <label for="phone-number">Phone number</label>
              <input type="text" id="phone-number" name="phone-number" required>
            </div>
            <div class="info">
              <label for="mobile-number">Mobile number</label>
              <input type="text" id="mobile-number" name="mobile-number" required>
            </div>
            <div class="info">
              <label for="fax">Fax</label>
              <input type="text" id="fax" name="fax" required>
            </div>
            <div class="info">
              <label for="other">Other</label>
              <input type="text" id="other" name="other" required>
            </div>
            <div class="info">
              <label for="website">Website</label>
              <input type="url" id="website" name="website" required>
            </div>
          </div>

          <div class="submit-persnal-info">
            <button type="button" onclick="location.href='dashboard.php'" style="color: rgb(173, 0, 0); border: 1px solid rgb(173, 0, 0);">Back</button>
            <button type="submit" name="submit">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>

<script>
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



   //forms
   // JavaScript to handle multi-step form navigation and validation
   

       const forms = document.querySelectorAll('.forms-container');
   let currentFormIndex = 0;

   function showForm(index) {
   forms.forEach((form, i) => {
      form.style.display = i === index ? 'block' : 'none';
   });
   }

   function validateForm(form) {
   const inputs = form.querySelectorAll('input');
   for (const input of inputs) {
      if (!input.checkValidity()) {
         alert('Please fill in all required fields correctly.');
         return false;
      }
   }
   return true;
   }

   function goToNextForm() {
   const currentForm = forms[currentFormIndex];
   if (validateForm(currentForm)) {
      currentFormIndex++;
      if (currentFormIndex < forms.length) {
         showForm(currentFormIndex);
      }
   }
   }

   function goToPreviousForm() {
   if (currentFormIndex > 0) {
      currentFormIndex--;
      showForm(currentFormIndex);
   }
   }

   // Add event listeners to buttons
   document.querySelectorAll('button').forEach(button => {
   if (button.textContent === 'Next') {
      button.addEventListener('click', (e) => {
         e.preventDefault();
         goToNextForm();
      });
   }

   if (button.textContent === 'Back') {
      button.addEventListener('click', (e) => {
         e.preventDefault();
         goToPreviousForm();
      });
   }

   if (button.textContent === 'Submit') {
      button.addEventListener('click', (e) => {
         e.preventDefault();
         if (validateForm(forms[currentFormIndex])) {
         alert('Form submitted successfully!');
         }
      });
   }
   });

   // Initialize the first form
   showForm(0);

</script>
</body>
</html>
