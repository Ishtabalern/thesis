<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Document</title>
   <link rel="stylesheet" href="styles/generateReport-employee.css">
   <link rel="stylesheet" href="styles/sidebar.css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

   <div class="sidebar">
      <div class="company-logo">
          <img src="./imgs/csk_logo.png" alt="">
      </div>
      <div class="btn-container">
          <a class="btn-tabs" href="dashboard.php" class="active"><i class="fa-solid fa-house"></i>Home</a>
          <a class="btn-tabs" href="scan.php"><i class="fa-solid fa-camera"></i>Capture Documents</a>
          <a class="btn-tabs" href="records.php"><i class="fa-solid fa-file"></i>Financial Records</a>
          <a class="btn-tabs" href="generateReport-employee.php"><i class="fa-solid fa-file-export"></i>Generate Report</a>
          <a class="btn-tabs" href="#"><i class="fa-solid fa-gear"></i>Settings</a>
      </div>
  </div>


  <main class="dashboard">

      <div class="top-bar">
          <h1>Generate Report</h1>
          <div class="user-controls"> 
              <button class="logout-btn">Log out</button>
              <div class="dropdown">
                  <button class="dropbtn">Employee ▼</button>
              </div>
          </div>
      </div>

      <section class="client-dropdown-section">
         <div class="dropdown-client-category">
            <button class="buttons"><span class="label-client">Client</span> <span>▼</span></button>
           <div class="dropdown-content">
             <button class="btn-client" data-id="client-one">7/11 - Salawag Branch</button>
             <button class="btn-client" data-id="client-two">7/11 - Paliparan Branch</button>
           </div>     
         </div>
       </section>

       <section class="client-content">
         <header>Monthly Financial Report</header>
         
         <div class="overall" >
            <div class="client-monthly" >
               <span>Client Balance 
                  (Last Month)</span>
               <span>₱ 20</span>
            </div>
            <div class="client-monthly" >
               <span>Monthly Balance</span>
               <span>₱ 20000</span>
            </div>
         </div>

         <div class="tables-container">
            
            <div class="table" style="border-right: 1px solid #919191; ">
               <table>
                  <thead>
                     <tr>
                        <th></th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Amount</th>
                     </tr>
                  </thead>

                  <tbody>
                     <tr>
                        <td>1.</td>
                        <td>1/1/24</td>
                        <td>Sales</td>
                        <td>69</td>
                     </tr>
                  </tbody>                
               </table>
            </div>

            <div class="table">
               <table>
                  <thead>
                     <tr>
                        <th></th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Amount</th>
                     </tr>
                  </thead>

                  <tbody>
                     <tr>
                        <td>1.</td>
                        <td>1/1/24</td>
                        <td>Expense on materials </td>
                        <td>69</td>
                     </tr>
                  </tbody>                
               </table>
            </div>
         </div>
       </section>

  </main>




  <script type="module" src="./script/generateReport-employee.js"></script>
</body>
</html>