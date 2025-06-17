<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['fullname'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit;
}

require_once 'db_connect.php'; // Include the database connection file

// Fetch data from the database
$query = "SELECT 
            item_code AS code, 
            item_description AS description, 
            pharmacologic_category AS category, 
            route, 
            if_intravenous AS iv, 
            high_alert_medication AS high_alert, 
            requiring_s2 AS s2, 
            requiring_yellow_rx AS yellow_rx 
          FROM simc_library";
$result = $conn->query($query);

$medications = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['high_alert'] = $row['high_alert'] === 'YES';
        $row['s2'] = $row['s2'] === 'YES';
        $row['yellow_rx'] = $row['yellow_rx'] === 'YES';
        $medications[] = $row;
    }
}

// Optimize search functionality with prepared statements
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filtered_medications = [];

if (!empty($search)) {
    $query = "SELECT 
                item_code AS code, 
                item_description AS description, 
                pharmacologic_category AS category, 
                route, 
                if_intravenous AS iv, 
                high_alert_medication AS high_alert, 
                requiring_s2 AS s2, 
                requiring_yellow_rx AS yellow_rx 
              FROM simc_library
              WHERE item_code LIKE ? OR item_description LIKE ?";
    $stmt = $conn->prepare($query);
    $searchTerm = "%$search%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['high_alert'] = $row['high_alert'] === 'YES';
            $row['s2'] = $row['s2'] === 'YES';
            $row['yellow_rx'] = $row['yellow_rx'] === 'YES';
            $filtered_medications[] = $row;
        }
    } 
    $stmt->close();
} else {
    $filtered_medications = $medications;
}

// Add a function to highlight search terms
function highlightSearch($text, $search, $med) {
    $highlightColor = '';
    if ($med['high_alert']) {
        $highlightColor = 'red'; // Changed to red for high alert
    } elseif ($med['yellow_rx']) {
        $highlightColor = 'yellow'; // Changed to yellow for requiring yellow Rx
    } elseif ($med['s2']) {
        $highlightColor = 'blue'; // Changed to blue for S2 requiring
    }
    $style = $highlightColor ? "background-color: $highlightColor;" : '';
    $style .= stripos($med['description'], 'dizziness') !== false ? ' font-style: italic;' : '';
    return str_ireplace($search, "<span style=\"$style\">$search</span>", $text);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MediScript Database</title>
  <link rel="stylesheet" href="style.css">
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    /* Ensure footer stays at the bottom */
    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      margin: 0;
    }

    .container {
      flex: 1;
    }

    footer {
      background-color: #f4f4f4;
      padding: 10px 20px;
      border-top: 1px solid #ccc;
    }

    footer div {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    footer img {
      height: 50px;
    }

    /* Add hover effect for table rows */
    table tbody tr:hover {
      background-color: #f0f8ff; /* Light blue background */
      transition: background-color 0.3s ease; /* Smooth transition */
    }

    /* Global Font Style */
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      font-size: 16px;
      color: #333;
    }

    /* Standardized Font Sizes */
    h1 {
      font-size: 2rem;
      font-weight: 600;
      color: #2c3e50;
    }

    h2 {
      font-size: 1.75rem;
      font-weight: 600;
      color: #2c3e50;
    }

    h3 {
      font-size: 1.5rem;
      font-weight: bold;
      color: #2c3e50;
    }

    p, label {
      font-size: 1rem;
      color: #34495e;
    }

    button {
      font-size: 1rem;
      font-weight: 600;
      font-family: inherit;
    }

    input, select {
      font-size: 1rem;
      font-family: inherit;
    }

    table th, table td {
      font-size: 1rem;
      font-family: inherit;
    }

/* EXTERNAL CSS PLUGIN */

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
  background-color: #f5f7fa;
  color: #333;
  padding: 20px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.logo {
  display: flex;
  align-items: center;
}

.logo svg {
  width: 40px;
  height: 40px;
  margin-right: 10px;
  fill: #3498db;
}

h1 {
  font-size: 24px;
  font-weight: 600;
  color: #2c3e50;
}

.search-container {
  background-color: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
  display: flex;
  align-items: center;
}

.search-icon {
  margin-right: 10px;
  color: #3498db;
}

.search-box {
  display: flex;
  align-items: center;
  flex: 1;
}

.search-input {
  flex: 1;
  padding: 12px 16px;
  border: 1px solid #ddd;
  border-radius: 6px 0 0 6px;
  font-size: 16px;
  outline: none;
  transition: border-color 0.2s;
}

.search-input:focus {
  border-color: #3498db;
}

.search-button {
  background-color: #3498db;
  color: white;
  border: none;
  border-radius: 0 6px 6px 0;
  padding: 12px 24px;
  font-size: 16px;
  cursor: pointer;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
}

.search-button:hover {
  background-color: #2980b9;
}

.search-button svg {
  margin-right: 8px;
}

.table-container {
  background-color: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.table-header {
  padding: 15px 20px;
  background-color: #f8f9fa;
  border-bottom: 1px solid #eee;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.table-title {
  font-size: 18px;
  font-weight: 600;
  color: #34495e;
  display: flex;
  align-items: center;
}

.table-title svg {
  margin-right: 10px;
  color: #3498db;
}

.stats {
  display: flex;
  align-items: center;
}

.stat {
  display: flex;
  align-items: center;
  margin-left: 15px;
  font-size: 14px;
  color: #7f8c8d;
}

.stat svg {
  margin-right: 5px;
  color: #3498db;
}

table {
  width: 100%;
  border-collapse: collapse;
  background-color: #ffffff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

th {
  background-color: #3498db;
  color: white;
  padding: 12px 16px;
  text-align: left;
  font-weight: 600;
  border-bottom: 2px solid #ddd;
  text-transform: uppercase;
}

td {
  padding: 12px 16px;
  border-bottom: 1px solid #eee;
  color: #34495e;
  font-size: 14px;
}

tr:last-child td {
  border-bottom: none;
}

tr:hover {
  background-color: #f5f7fa;
  transition: background-color 0.3s ease;
}

tr:nth-child(even) {
  background-color: #f9f9f9;
}

.status-icon {
  font-size: 18px;
  display: flex;
  justify-content: center;
}

.icon-true {
  color: #2ecc71;
}

.icon-false {
  color: #e74c3c;
}

.high-alert {
  position: relative;
}

.high-alert-icon {
  background-color: #e74c3c;
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
}

.tag {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  margin-right: 4px;
}

.tag-category {
  background-color: #e8f4fd;
  color: #3498db;
}

.tag-route {
  background-color: #e8f8f5;
  color: #1abc9c;
}

.tag-oral {
  background-color: #e8f8f5;
  color: #1abc9c;
}

.tag-iv {
  background-color: #fde8e8;
  color: #e74c3c;
}

.tag-inhalation {
  background-color: #f4e8fd;
  color: #9b59b6;
}

.tag-subcutaneous {
  background-color: #fef8e8;
  color: #f39c12;
}

.stats-cards {
  display: flex;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 15px;
}

.stat-card {
  background-color: white;
  border-radius: 8px;
  padding: 15px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  flex: 1;
  min-width: 200px;
  display: flex;
  align-items: center;
}

.stat-card-icon {
  background-color: #ebf5ff;
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
}

.stat-card-icon svg {
  color: #3498db;
  width: 24px;
  height: 24px;
}

.stat-card-content h3 {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 5px;
  color: #2c3e50;
}

.stat-card-content p {
  font-size: 14px;
  color: #7f8c8d;
  margin: 0;
}

.yellow-rx-icon {
  color: #f1c40f;
  margin: 0 auto;
  display: block;
}

@media (max-width: 768px) {
  .header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .logo {
    margin-bottom: 10px;
  }
  
  .stats-cards {
    flex-direction: column;
  }
  
  .search-box {
    width: 100%;
  }
  
  .table-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .stats {
    margin-top: 10px;
  }
  
  .stat {
    margin-left: 0;
    margin-right: 15px;
  }
  
  table {
    display: block;
    overflow-x: auto;
  }
  
  th, td {
    white-space: nowrap;
  }
}

/* Responsive table for very small screens */
@media (max-width: 480px) {
  .table-container {
    overflow-x: auto;
  }
}

/* Modal container styling */
#modalContent {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.modal-section {
  background: #f9f9f9;
  padding: 15px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.modal-section h3 {
  margin-bottom: 10px;
  font-size: 18px;
  color: #2c3e50;
  font-weight: bold;
}

.modal-section p {
  margin: 5px 0;
  font-size: 14px;
  color: #34495e;
}

/* Item code styling */
#modalItemCode {
  position: absolute;
  top: 15px;
  left: 15px; /* Move to the left */
  background: #3498db;
  color: white;
  padding: 10px 15px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: bold;
  display: flex;
  align-items: center;
  gap: 8px;
}

#modalItemCode svg {
  width: 16px;
  height: 16px;
  fill: white;
}

#modalItemCode span {
  display: flex;
  flex-direction: column;
}

#modalItemCode .label {
  font-size: 12px;
  font-weight: normal;
  opacity: 0.8;
}
/* Responsive styles */
@media (max-width: 768px) {
    .stats-cards {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .stat-card {
      flex: 1;
      text-align: center;
    }

    .search-container {
      flex-direction: column;
      align-items: stretch;
    }

    .search-box {
      width: 100%;
    }

    .table-container {
      overflow-x: auto;
    }

    table {
      width: 100%;
      font-size: 14px;
    }

    .table-header {
      flex-direction: column;
      align-items: flex-start;
    }

    .table-title {
      margin-bottom: 10px;
    }

    .stats {
      flex-direction: column;
      gap: 10px;
    }

    .stats .stat {
      font-size: 14px;
    }

    #detailsModal {
      width: 90%;
      margin: 5% auto;
    }
  }

  @media (max-width: 480px) {
    .stat-card-content h3 {
      font-size: 18px;
    }

    .stat-card-content p {
      font-size: 14px;
    }

    .search-input {
      font-size: 14px;
    }

    .search-button {
      font-size: 12px;
    }

    .modal-section h3 {
      font-size: 16px;
    }

    .modal-section p {
      font-size: 14px;
    }
  }
  /* Loading animation styles */
  .loading svg polyline {
    fill: none;
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .loading svg polyline#back {
    fill: none;
    stroke: #ff4d5033;
  }

  .loading svg polyline#front {
    fill: none;
    stroke: #00ffff;
    stroke-dasharray: 48, 144;
    stroke-dashoffset: 192;
    animation: dash_682 2s linear infinite;
    animation-delay: 0s;
  }

  .loading svg polyline#front2 {
    fill: none;
    stroke: #00ffff;
    stroke-dasharray: 48, 144;
    stroke-dashoffset: 192;
    animation: dash_682 2s linear infinite;
    animation-delay: 1s;
  }

  @keyframes dash_682 {
    72.5% {
      opacity: 0;
    }
    to {
      stroke-dashoffset: 0;
    }
  }
  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: scale(0.9);
    }
    to {
      opacity: 1;
      transform: scale(1);
    }
  }
  
/* Add styles for Add Medication button and modals */
.add-medication-button {
  background-color: #28a745;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 5px;
  cursor: pointer;
  font-size: 16px;
  margin-right: 20px;
}

.add-medication-button:hover {
  background-color: #218838;
}

#addMedicationModal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  z-index: 10000;
  overflow-y: auto;
}

#addMedicationModal > div {
  background: white;
  margin: 5% auto;
  padding: 20px;
  width: 90%;
  max-width: 500px;
  border-radius: 16px;
  position: relative;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

#addMedicationModal h2 {
  text-align: center;
  color: #2c3e50;
  margin-bottom: 20px;
  font-size: 24px;
}

#addMedicationForm {
  display: flex;
  flex-direction: column;
  gap: 15px;
  max-height: 70vh;
  overflow-y: auto;
}

#addMedicationForm label {
  font-weight: bold;
}

#addMedicationForm input,
#addMedicationForm select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 5px;
}

#addMedicationForm button {
  background-color: #28a745;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 16px;
}

#addMedicationForm button:hover {
  background-color: #218838;
}

#addMedicationModal > div > div {
  display: flex;
  justify-content: space-between;
  margin-top: 20px;
}

#addMedicationModal > div > div button:first-child {
  background-color: #e74c3c;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 16px;
}

#addMedicationModal > div > div button:last-child {
  background-color: #28a745;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 16px;
}

@media (max-width: 768px) {
  #addMedicationModal > div {
    width: 95%;
    padding: 20px;
  }

  #addMedicationModal h2 {
    font-size: 20px;
  }

  #addMedicationModal button {
    font-size: 14px;
  }
}

/* Updated Add Medication Button Style */
.button {
  position: relative;
  width: 150px;
  height: 40px;
  cursor: pointer;
  display: flex;
  align-items: center;
  border: 1px solid #34974d;
  background-color: #3aa856;
}

.button, .button__icon, .button__text {
  transition: all 0.3s;
}

.button .button__text {
  transform: translateX(30px);
  color: #fff;
  font-weight: 600;
}

.button .button__icon {
  position: absolute;
  transform: translateX(109px);
  height: 100%;
  width: 39px;
  background-color: #34974d;
  display: flex;
  align-items: center;
  justify-content: center;
}

.button .svg {
  width: 30px;
  stroke: #fff;
}

.button:hover {
  background: #34974d;
}

.button:hover .button__text {
  color: transparent;
}

.button:hover .button__icon {
  width: 148px;
  transform: translateX(0);
}

.button:active .button__icon {
  background-color: #2e8644;
}

.button:active {
  border: 1px solid #2e8644;
}

  </style>
</head>
<body>
  <!-- Add dropdown button for user -->
  <div style="position: absolute; top: 10px; right: 20px;">
    <div style="position: relative; display: inline-block;">
      <button onclick="toggleDropdown()" style="background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
        <?php echo htmlspecialchars($_SESSION['fullname']); ?>
      </button>
      <div id="userDropdown" style="display: none; position: absolute; right: 0; background-color: white; border: 1px solid #ccc; border-radius: 5px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); z-index: 1000; overflow: hidden;">
        <a href="profile.php" style="display: block; padding: 10px 20px; text-decoration: none; color: #333;">Profile</a>
        <a href="index.php" style="display: block; padding: 10px 20px; text-decoration: none; color: #333;">Main</a>
        <a href="logout.php" style="display: block; padding: 10px 20px; text-decoration: none; color: #333;">Logout</a>
      </div>
    </div>
  </div>

  <script>
    function toggleDropdown() {
        const dropdown = document.getElementById('userDropdown');
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }

    window.addEventListener('click', function(event) {
        const dropdown = document.getElementById('userDropdown');
        const button = document.querySelector('button[onclick="toggleDropdown()"]');
        if (!dropdown.contains(event.target) && event.target !== button) {
            dropdown.style.display = 'none';
        }
    });
  </script>

  <div class="container">
    <div class="header">
      <div class="logo" style="display: flex; align-items: center; gap: 10px;">
        <div style="width: 60px; height: 60px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background-color: #f4f4f4;">
          <img src="logo/logo2.gif" alt="MediScript Logo" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <h1 style="margin: 0;">MediScript Database</h1>
      </div>
    </div>

    <!-- Move the Add Medication button to the top -->
    <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
      <button type="button" class="button" onclick="openAddModal()">
        <span class="button__text">Add Item</span>
        <span class="button__icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 24 24" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" stroke="currentColor" height="24" fill="none" class="svg">
            <line y2="19" y1="5" x2="12" x1="12"></line>
            <line y2="12" y1="12" x2="19" x1="5"></line>
          </svg>
        </span>
      </button>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-cards">
      <div class="stat-card">
        <div class="stat-card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M19 3H5c-1.1 0-1.99.9-1.99 2L3 19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z"/>
          </svg>
        </div>
        <div class="stat-card-content">
          <h3><?php echo count($medications); ?></h3>
          <p>Total Medications</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-card-icon" style="background-color: #fef8e8;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#f39c12">
            <path d="M10.01 21.01c0 1.1.89 1.99 1.99 1.99s1.99-.89 1.99-1.99h-3.98zM12 6c2.76 0 5 2.24 5 5v7H7v-7c0-2.76 2.24-5 5-5zm0-4.5c-.83 0-1.5.67-1.5 1.5v1.17C7.36 4.85 5 7.65 5 11v6l-2 2v1h18v-1l-2-2v-6c0-3.35-2.36-6.15-5.5-6.83V3c0-.83-.67-1.5-1.5-1.5z"/>
          </svg>
        </div>
        <div class="stat-card-content">
          <h3><?php echo count(array_filter($medications, function($med) { return $med['high_alert']; })); ?></h3>
          <p>High Alert Medications</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-card-icon" style="background-color: #e8f8f5;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#1abc9c">
            <path d="M18 4H6C4.9 4.9 4.9 4.9 4 6v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H6V6h12v12zm-6-7c.83 0 1.5-.67 1.5-1.5S12.83 8 12 8s-1.5.67-1.5 1.5.67 1.5 1.5 1.5zm0-3c.83 0 1.5.67 1.5 1.5S12.83 10 12 10s-1.5-.67-1.5-1.5.67-1.5 1.5-1.5zm0 4.5c-1.58 0-4.5.8-4.5 2.4v1.1h9v-1.1c0-1.6-2.92-2.4-4.5-2.4zm0 1.2c1.33 0 2.8.53 3.3 1.2H8.7c.5-.67 1.97-1.2 3.3-1.2z"/>
          </svg>
        </div>
        <div class="stat-card-content">
          <h3><?php echo count(array_filter($medications, function($med) { return $med['s2']; })); ?></h3>
          <p>S2 Medications</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-card-icon" style="background-color: #fef8e8;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#f1c40f">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-3.59 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8-3.59-8-8-3.59 8-8 8zm4.59-12.42L10 14.17l-2.59-2.58L6 13l4 4 8-8z"/>
          </svg>
        </div>
        <div class="stat-card-content">
          <h3><?php echo count(array_filter($medications, function($med) { return $med['yellow_rx']; })); ?></h3>
          <p>Yellow Rx Medications</p>
        </div>
      </div>
    </div>
    
    <form action="" method="GET">
      <div class="search-container">
        <div class="search-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
          </svg>
        </div>
        <div class="search-box">
          <input type="text" name="search" class="search-input" placeholder="Search by Item Code or Description..." value="<?php echo htmlspecialchars($search); ?>">
          <button type="submit" class="search-button">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
            </svg>
            Search
          </button>
        </div>
      </div>
    </form>
    
    <div class="table-container">
      <div class="table-header">
        <div class="table-title"> 
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12zM10 9h8v2h-8zm0-3h8v2h-8zm0 6h4v2h-4z"/>
          </svg>
          Medication List
        </div>
        <div class="stats">
          <div class="stat">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"></svg></svg>
              <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            <?php echo count($filtered_medications); ?> medications
          </div>
          <?php if (!empty($search)): ?>
          <div class="stat">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
            </svg>
            Search: "<?php echo htmlspecialchars($search); ?>"
          </div>
          <?php endif; ?>
        </div>
        <div style="margin-top: 10px; display: flex; gap: 15px;">
          <div style="display: flex; align-items: center; gap: 5px;">
            <div style="width: 20px; height: 20px; background-color: red; border: 1px solid #ccc;"></div>
            <span>High Alert</span>
          </div>
          <div style="display: flex; align-items: center; gap: 5px;">
            <div style="width: 20px; height: 20px; background-color: yellow; border: 1px solid #ccc;"></div>
            <span>Yellow Rx</span>
          </div>
          <div style="display: flex; align-items: center; gap: 5px;">
            <div style="width: 20px; height: 20px; background-color: blue; border: 1px solid #ccc;"></div>
            <span>S2</span>
          </div>
          <div style="display: flex; align-items: center; gap: 5px;">
            <div style="width: 20px; height: 20px; background-color: green; border: 1px solid #ccc;"></div>
            <span>Causes Dizziness</span>
          </div>
        </div>
      </div>
      <table>
        <thead>
          <tr>
            <th>Item Description</th>
            <th style="text-align: center;">Color Code</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($filtered_medications)): ?>
          <tr>
            <td colspan="2" style="text-align: center; padding: 20px;">No medications found matching your search.</td>
          </tr>
          <?php else: ?>
          <?php foreach ($filtered_medications as $med): ?>
          <tr onclick="showDetails(<?php echo htmlspecialchars(json_encode($med)); ?>)">
            <td>
              <?php echo htmlspecialchars($med['description']); ?>
            </td>
            <td style="text-align: center;">
              <div style="display: flex; justify-content: center;">
                <div style="width: 100px; height: 30px; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; border-radius: 5px; background-color: #f9f9f9; overflow: hidden;">
                  <?php 
                  $colors = [];
                  if ($med['high_alert']) $colors[] = 'red'; // Changed to red for high alert
                  if ($med['yellow_rx']) $colors[] = 'yellow'; // Changed to yellow for requiring yellow Rx
                  if ($med['s2']) $colors[] = 'blue'; // Changed to blue for S2 requiring
                  if (stripos($med['description'], 'dizziness') !== false) $colors[] = 'green';

                  $colorWidth = count($colors) > 0 ? (100 / count($colors)) . '%' : '100%';
                  foreach ($colors as $color): ?>
                  <div style="width: <?php echo $colorWidth; ?>; height: 100%; background-color: <?php echo $color; ?>;"></div>
                  <?php endforeach; ?>
                </div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add loading animation -->
  <div id="loading" class="loading" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); z-index: 9999; align-items: center; justify-content: center;">
    <svg width="64px" height="48px">
      <polyline style="stroke: blue; fill: none;" points="0.157 23.954, 14 23.954, 21.843 48, 43 0, 50 24, 64 24" id="back"></polyline>
      <polyline style="stroke: red; fill: none;" points="0.157 23.954, 14 23.954, 21.843 48, 43 0, 50 24, 64 24" id="front"></polyline>
      <polyline style="stroke: green; fill: none;" points="0.157 23.954, 14 23.954, 21.843 48, 43 0, 50 24, 64 24" id="front2"></polyline>
    </svg>
  </div>

  <style>
  /* Loading animation styles */
  .loading svg polyline {
    fill: none;
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .loading svg polyline#back {
    fill: none;
    stroke: #ff4d5033;
  }

  .loading svg polyline#front {
    fill: none;
    stroke: #00ffff;
    stroke-dasharray: 48, 144;
    stroke-dashoffset: 192;
    animation: dash_682 2s linear infinite;
    animation-delay: 0s;
  }

  .loading svg polyline#front2 {
    fill: none;
    stroke: #00ffff;
    stroke-dasharray: 48, 144;
    stroke-dashoffset: 192;
    animation: dash_682 2s linear infinite;
    animation-delay: 1s;
  }

  @keyframes dash_682 {
    72.5% {
      opacity: 0;
    }
    to {
      stroke-dashoffset: 0;
    }
  }

  /* Ensure modal is not covered */
  #detailsModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000; /* Increased z-index to ensure visibility */
  }

  #detailsModal > div {
    background: white;
    margin: 10% auto;
    padding: 30px;
    width: 50%;
    border-radius: 16px;
    position: relative;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    animation: fadeIn 0.3s ease-in-out;
    z-index: 10001; /* Ensure modal content is above other elements */
  }

  /* Adjust modal close button to ensure visibility */
  #detailsModal button {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #e74c3c;
    color: white;
    border: none;
    border-radius: 50%;
    width: 35px;
    height: 35px;
    cursor: pointer;
    font-size: 18px;
    font-weight: bold;
    z-index: 10002; /* Ensure close button is above modal content */
  }
  </style>

  <!-- Add modal HTML -->
  <div id="detailsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5);">
    <div style="
      background: white; 
      margin: 10% auto; 
      padding: 30px; 
      width: 50%; 
      border-radius: 16px; 
      position: relative; 
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3); 
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      animation: fadeIn 0.3s ease-in-out;">
      <div id="modalItemCode" style="display: flex; align-items: center; gap: 10px;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
          <path d="M19 3H5c-1.1 0-1.99.9-1.99 2L3 19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z"/>
        </svg>
        <span>
          <span class="label">Item Code</span>
          <span id="itemCodeText"></span>
        </span>
        <div id="modalColorIndicators" style="display: flex; gap: 5px;">
          <!-- Color indicators will be dynamically added here -->
        </div>
      </div>
      <button onclick="closeModal()" style="
        position: absolute; 
        top: 15px; 
        right: 15px; 
        background: #e74c3c; 
        color: white; 
        border: none; 
        border-radius: 50%; 
        width: 35px; 
        height: 35px; 
        cursor: pointer; 
        font-size: 18px; 
        font-weight: bold;">&times;</button>
      <h2 style="text-align: center; color: #2c3e50; margin-bottom: 20px; font-size: 24px;">Details</h2>
      <div id="modalContent">
        <div class="modal-section">
          <h3>Description</h3>
          <p id="modalDescription"></p>
        </div>
        <div class="modal-section">
          <h3>Pharmacologic Category or Dosage Form Classification</h3>
          <p id="modalCategory"></p>
        </div>
        <div class="modal-section">
          <h3>Details</h3>
          <p><strong>Route:</strong> <span id="modalRoute"></span></p>
          <p><strong>IF INTRAVENOUS:</strong> <span id="modalIV"></span></p>
          <p><strong>High Alert:</strong> <span id="modalHighAlert"></span></p>
          <p><strong>S2:</strong> <span id="modalS2"></span></p>
          <p><strong>Yellow Rx:</strong> <span id="modalYellowRx"></span></p>
        </div>
      </div> 
      <!-- Add Edit button at the bottom of the modal -->
      <div style="display: flex; justify-content: flex-end; margin-top: 0px;">
        <button onclick="openEditModal({
          code: document.getElementById('itemCodeText').innerText,
          description: document.getElementById('modalDescription').innerText,
          category: document.getElementById('modalCategory').innerText,
          route: document.getElementById('modalRoute').innerText,
          iv: document.getElementById('modalIV').innerText,
          high_alert: document.getElementById('modalHighAlert').innerText === 'Yes',
          s2: document.getElementById('modalS2').innerText === 'Yes',
          yellow_rx: document.getElementById('modalYellowRx').innerText === 'Yes'
        })" style="background-color: #28a745; color: white; padding: 10px 10px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%;padding:2px; max-width: 70px; right:80px">EDIT</button>
      </div>
    </div>
  </div>

  <!-- Add Medication Modal -->
  <div id="addMedicationModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000; overflow-y: auto;">
    <div style="background: white; margin: 5% auto; padding: 20px; width: 90%; max-width: 500px; border-radius: 16px; position: relative; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);">
      <h2 style="text-align: center; margin-bottom: 20px;">Add New Medication</h2>
      <form id="addMedicationForm" style="display: flex; flex-direction: column; gap: 15px; max-height: 70vh; overflow-y: auto;">
        <label for="addItemCode">Item Code:</label>
        <input type="text" id="addItemCode" name="item_code" required>

        <label for="addDescription">Description:</label>
        <input type="text" id="addDescription" name="description" required>

        <label for="addCategory">Category:</label>
        <input type="text" id="addCategory" name="category" required>

        <label for="addRoute">Route:</label>
        <input type="text" id="addRoute" name="route" required>

        <label for="addIV">IF INTRAVENOUS:</label>
        <select id="addIV" name="iv" required>
          <option value="">Select</option>
          <option value="YES">Yes</option>
          <option value="NO">No</option>
        </select>

        <label for="addHighAlert">High Alert:</label>
        <select id="addHighAlert" name="high_alert" required>
          <option value="">Select</option>
          <option value="YES">Yes</option>
          <option value="NO">No</option>
        </select>

        <label for="addS2">S2:</label>
        <select id="addS2" name="s2" required>
          <option value="">Select</option>
          <option value="YES">Yes</option>
          <option value="NO">No</option>
        </select>

        <label for="addYellowRx">Yellow Rx:</label>
        <select id="addYellowRx" name="yellow_rx" required>
          <option value="">Select</option>
          <option value="YES">Yes</option>
          <option value="NO">No</option>
        </select>
      </form>

      <!-- Buttons inside the modal -->
      <div style="display: flex; justify-content: space-between; margin-top: 20px;">
        <button type="button" onclick="closeAddModal()" style="background-color: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">Cancel</button>
        <button type="button" onclick="showAddConfirmationPopup()" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">Add</button>
      </div>
    </div>
  </div>

  <!-- Add confirmation popup for Add Medication -->
  <div id="addConfirmationPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000;">
    <div style="background: white; margin: 10% auto; padding: 20px; width: 30%; border-radius: 8px; text-align: center;">
      <h3>Confirm Add Medication</h3>
      <p>Are you sure you want to add this medication?</p>
      <button onclick="confirmAddMedication()" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Yes</button>
      <button onclick="closeAddConfirmationPopup()" style="background-color: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">No</button>
    </div>
  </div>

  <!-- Updated Edit Medication Modal -->
  <div id="editMedicationModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000; ">
    <div style="background: white; margin: 5% auto; padding: 20px; width: 90%; max-width: 500px; border-radius: 16px; position: relative; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);">
      <!-- Add Delete Medication button at the top-right corner of the Edit Medication modal -->
      <button onclick="showDeleteConfirmationPopup()" style="
        position: absolute;
        top: 15px;
        right: 15px;
        background-color: #e74c3c;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 10px 15px;
        cursor: pointer;
        font-size: 14px;">Delete</button>

      <h2 style="text-align: center; color: #2c3e50; margin-bottom: 20px; font-size: 24px;">Edit Medication</h2>
      <form id="editMedicationForm" style="display: flex; flex-direction: column; gap: 15px; max-height: 70vh; overflow-y: auto;">
        <label for="editItemCode" style="font-weight: bold;">Item Code:</label>
        <input type="text" id="editItemCode" name="item_code" readonly style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">

        <label for="editDescription" style="font-weight: bold;">Description:</label>
        <input type="text" id="editDescription" name="description" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">

        <label for="editCategory" style="font-weight: bold;">Category:</label>
        <input type="text" id="editCategory" name="category" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">

        <label for="editRoute" style="font-weight: bold;">Route:</label>
        <input type="text" id="editRoute" name="route" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">

        <label for="editIV" style="font-weight: bold;">IV:</label>
        <select id="editIV" name="iv" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
          <option value="YES">Yes</option>
          <option value="NO">No</option>
        </select>

        <label for="editHighAlert" style="font-weight: bold;">High Alert:</label>
        <select id="editHighAlert" name="high_alert" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
          <option value="YES">Yes</option>
          <option value="NO">No</option>
        </select>

        <label for="editS2" style="font-weight: bold;">S2:</label>
        <select id="editS2" name="s2" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
          <option value="YES">Yes</option>
          <option value="NO">No</option>
        </select>

        <label for="editYellowRx" style="font-weight: bold;">Yellow Rx:</label>
        <select id="editYellowRx" name="yellow_rx" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
          <option value="YES">Yes</option>
          <option value="NO">No</option>
        </select>
      </form>

      <!-- Buttons at the bottom -->
      <div style="display: flex; justify-content: space-between; margin-top: 20px;">
        <button type="button" onclick="closeEditModal()" style="background-color:rgb(96, 96, 96); color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">Cancel</button>
        <button type="button" onclick="showEditConfirmationPopup()" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">Save</button>
      </div>
    </div>
  </div>

  <!-- Confirmation Popup for Edit Medication -->
  <div id="editConfirmationPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000;">
    <div style="background: white; margin: 10% auto; padding: 20px; width: 30%; border-radius: 8px; text-align: center;">
      <h3>Confirm Edit Medication</h3>
      <p>Are you sure you want to save changes to this medication?</p>
      <button onclick="confirmEditMedication()" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Yes</button>
      <button onclick="closeEditConfirmationPopup()" style="background-color: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">No</button>
    </div>
  </div>

  <!-- Confirmation Popup for Delete Medication -->
  <div id="deleteConfirmationPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000;">
    <div style="background: white; margin: 10% auto; padding: 20px; width: 30%; border-radius: 8px; text-align: center;">
      <h3>Confirm Delete Medication</h3>
      <p>Are you sure you want to delete this medication?</p>
      <button onclick="confirmDeleteMedication()" style="background-color: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Yes</button>
      <button onclick="closeDeleteConfirmationPopup()" style="background-color: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">No</button>
    </div>
  </div>

  <!-- Success Popup for Deletion -->
  <div id="deleteSuccessPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000;">
    <div style="background: white; margin: 10% auto; padding: 20px; width: 30%; border-radius: 8px; text-align: center;">
      <h3>Deletion Successful</h3>
      <p>The medication has been successfully deleted.</p>
      <button onclick="closeDeleteSuccessPopup()" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">OK</button>
    </div>
  </div>

  <!-- Success Popup for Adding Medication -->
  <div id="addSuccessPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000;">
    <div style="background: white; margin: 10% auto; padding: 20px; width: 30%; border-radius: 8px; text-align: center;">
      <h3>Addition Successful</h3>
      <p>The medication has been successfully added.</p>
      <button onclick="closeAddSuccessPopup()" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">OK</button>
    </div>
  </div>

  <!-- Success Popup for Editing Medication -->
  <div id="editSuccessPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000;">
    <div style="background: white; margin: 10% auto; padding: 20px; width: 30%; border-radius: 8px; text-align: center;">
      <h3>Edit Successful</h3>
      <p>The medication has been successfully updated.</p>
      <button onclick="closeEditSuccessPopup()" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">OK</button>
    </div>
  </div>

  <!-- No Results Popup -->
  <div id="noResultsPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000;">
    <div style="background: white; margin: 10% auto; padding: 20px; width: 30%; border-radius: 8px; text-align: center;">
      <h3>No Results Found</h3>
      <p>No medications match your search term.</p>
      <button onclick="closeNoResultsPopup()" style="background-color: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">OK</button>
    </div>
  </div>

  <style>
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: scale(0.9);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }
  </style>

  <!-- Add JavaScript for modal functionality -->
  <script>
    function showDetails(med) {
      const modal = document.getElementById('detailsModal');
      const modalContent = modal.querySelector('div');

      // Remove modal background color logic
      modalContent.style.backgroundColor = '#ffffff'; // Default white

      // Populate modal details
      document.getElementById('itemCodeText').innerText = med.code;
      document.getElementById('modalDescription').innerText = med.description;
      document.getElementById('modalCategory').innerText = med.category;
      document.getElementById('modalRoute').innerText = med.route;
      document.getElementById('modalIV').innerText = med.iv;
      document.getElementById('modalHighAlert').innerText = med.high_alert ? 'Yes' : 'No';
      document.getElementById('modalS2').innerText = med.s2 ? 'Yes' : 'No';
      document.getElementById('modalYellowRx').innerText = med.yellow_rx ? 'Yes' : 'No';

      // Add color indicators
      const colorIndicators = document.getElementById('modalColorIndicators');
      colorIndicators.innerHTML = ''; // Clear existing indicators
      const colors = [];
      if (med.high_alert) colors.push('red');
      if (med.yellow_rx) colors.push('yellow');
      if (med.s2) colors.push('blue');
      if (med.description.toLowerCase().includes('dizziness')) colors.push('green');

      const colorWidth = colors.length > 0 ? (100 / colors.length) + '%' : '100%';
      const indicatorContainer = document.createElement('div');
      indicatorContainer.style.cssText = `
          width: 100px; 
          height: 30px; 
          display: flex; 
          align-items: center; 
          justify-content: center; 
          border: 1px solid #ccc; 
          border-radius: 5px; 
          background-color: #f9f9f9; 
          overflow: hidden;
      `;

      colors.forEach(color => {
          const colorDiv = document.createElement('div');
          colorDiv.style.cssText = `
              width: ${colorWidth}; 
              height: 100%; 
              background-color: ${color};
          `;
          indicatorContainer.appendChild(colorDiv);
      });

      colorIndicators.appendChild(indicatorContainer);

      // Show modal
      modal.style.display = 'block';
    }

    function closeModal() {
      document.getElementById('detailsModal').style.display = 'none';
    }

    // Show loading animation when search is triggered
    const searchForm = document.querySelector('form');
    searchForm.addEventListener('submit', function (event) {
        event.preventDefault(); // Prevent immediate form submission
        const loadingElement = document.getElementById('loading');
        loadingElement.style.display = 'flex';

        // Add a 2-second delay before submitting the form
        setTimeout(() => {
            searchForm.submit();
        }, 2000);
    });

    function openAddModal() {
      document.getElementById('addMedicationModal').style.display = 'block';
    }

    function closeAddModal() {
      document.getElementById('addMedicationModal').style.display = 'none';
    }

    function showAddConfirmationPopup() {
      const form = document.getElementById('addMedicationForm');
      if (form.checkValidity()) {
        document.getElementById('addConfirmationPopup').style.display = 'block';
      } else {
        alert('Please fill out all required fields.');
      }
    }

    function closeAddConfirmationPopup() {
      document.getElementById('addConfirmationPopup').style.display = 'none';
    }

    function confirmAddMedication() {
      closeAddConfirmationPopup();
      submitAddMedication();
    }

    function showAddSuccessPopup() {
      document.getElementById('addSuccessPopup').style.display = 'block';
    }

    function closeAddSuccessPopup() {
      document.getElementById('addSuccessPopup').style.display = 'none';
      location.reload();
    }

    function submitAddMedication() {
      const form = document.getElementById('addMedicationForm');
      const formData = new FormData(form);

      fetch('add_medication.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showAddSuccessPopup();
        } else {
          alert('Error adding medication: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the medication.');
      });
    }

    function openEditModal(med) {
      document.getElementById('editItemCode').value = med.code;
      document.getElementById('editDescription').value = med.description;
      document.getElementById('editCategory').value = med.category;
      document.getElementById('editRoute').value = med.route;
      document.getElementById('editIV').value = med.iv;
      document.getElementById('editHighAlert').value = med.high_alert ? 'YES' : 'NO';
      document.getElementById('editS2').value = med.s2 ? 'YES' : 'NO';
      document.getElementById('editYellowRx').value = med.yellow_rx ? 'YES' : 'NO';

      document.getElementById('editMedicationModal').style.display = 'block';
    }

    function closeEditModal() {
      document.getElementById('editMedicationModal').style.display = 'none';
    }

    function showEditConfirmationPopup() {
      const form = document.getElementById('editMedicationForm');
      if (form.checkValidity()) {
        document.getElementById('editConfirmationPopup').style.display = 'block';
      } else {
        alert('Please fill out all required fields.');
      }
    }

    function closeEditConfirmationPopup() {
      document.getElementById('editConfirmationPopup').style.display = 'none';
    }

    function confirmEditMedication() {
      closeEditConfirmationPopup();
      submitEditMedication();
    }

    function showEditSuccessPopup() {
      document.getElementById('editSuccessPopup').style.display = 'block';
    }

    function closeEditSuccessPopup() {
      document.getElementById('editSuccessPopup').style.display = 'none';
      location.reload();
    }

    function submitEditMedication() {
      const form = document.getElementById('editMedicationForm');
      const formData = new FormData(form);

      fetch('edit_medication.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showEditSuccessPopup();
        } else {
          alert('Error updating medication: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the medication.');
      });
    }

    function showDeleteConfirmationPopup() {
      document.getElementById('deleteConfirmationPopup').style.display = 'block';
    }

    function closeDeleteConfirmationPopup() {
      document.getElementById('deleteConfirmationPopup').style.display = 'none';
    }

    function confirmDeleteMedication() {
      closeDeleteConfirmationPopup();
      deleteMedication();
    }

    function showDeleteSuccessPopup() {
      document.getElementById('deleteSuccessPopup').style.display = 'block';
    }

    function closeDeleteSuccessPopup() {
      document.getElementById('deleteSuccessPopup').style.display = 'none';
      location.reload();
    }

    function deleteMedication() {
      const itemCode = document.getElementById('editItemCode').value;
      if (!itemCode) {
        alert('No medication selected to delete.');
        return;
      }

      fetch(`delete_medication.php?item_code=${encodeURIComponent(itemCode)}`, {
        method: 'GET'
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showDeleteSuccessPopup();
        } else {
          alert('Error deleting medication: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the medication.');
      });
    }

    function showNoResultsPopup() {
      document.getElementById('noResultsPopup').style.display = 'block';
    }

    function closeNoResultsPopup() {
      document.getElementById('noResultsPopup').style.display = 'none';
    }

    // Check if no results and show popup
    document.addEventListener('DOMContentLoaded', function() {
      const searchResults = <?php echo json_encode($filtered_medications); ?>;
      const searchTerm = <?php echo json_encode($search); ?>;

      if (searchTerm && searchResults.length === 0) {
        showNoResultsPopup();
      }
    });
  </script>

<footer>
</body>
</html>