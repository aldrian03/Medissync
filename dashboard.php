<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Sample user and revenue
$user = htmlspecialchars($_SESSION['user']);
$revenue = 15420.75;

// Set up the database connection
$servername = "localhost";
$username = "root";
$password = ""; // Or your actual password
$database = "medlog"; // Your actual DB name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if prescriptions are already in the session
if (!isset($_SESSION['prescriptions'])) {
    // Fetch prescriptions from a database (or real-time source)
    $query = "SELECT patient_name, medicine, dosage FROM prescriptions"; // Adjust query as per your actual table structure
    $result = mysqli_query($conn, $query);

    $prescriptions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $prescriptions[] = [$row['patient_name'], $row['medicine'], $row['dosage']];
    }

    // If no prescriptions are found, set an empty array
    if (empty($prescriptions)) {
        $prescriptions = [];
    }

    // Store the real-time prescriptions in the session
    $_SESSION['prescriptions'] = $prescriptions;
}

// Handle add‐prescription form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient'], $_POST['medicine'], $_POST['dosage'])) {
    $patient  = htmlspecialchars($_POST['patient']);
    $medicine = htmlspecialchars($_POST['medicine']);
    $dosage   = htmlspecialchars($_POST['dosage']);
    array_unshift($_SESSION['prescriptions'], [$patient, $medicine, $dosage]);
}

// query to get today's appointments
$today = date('Y-m-d');
$query = "SELECT * FROM appointments WHERE appointment_date = '$today'";
$result = mysqli_query($conn, $query);
$appointmentsToday = mysqli_fetch_all($result, MYSQLI_ASSOC);






$prescriptions = $_SESSION['prescriptions'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard</title>
  <style>
  body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f5f5f5;
    display: flex;
  }

  .sidebar {
    width: 250px;
    background: #4b7c67;
    height: 100vh;
    position: fixed;
    top: 0;
    padding: 20px;
    color: white;
    transition: transform 0.3s;
  }

  .sidebar.hidden {
    transform: translateX(-100%);
  }

  .sidebar h3 {
    margin: 0 0 30px;
    color: black;
  }

  .sidebar a {
    color: white;
    text-decoration: none;
    margin-bottom: 20px;
    display: block;
  }

  .main-content {
    margin-left: 250px;
    flex: 1;
    transition: margin-left 0.3s;
  }

  .main-content.full {
    margin-left: 0;
  }

  .header {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px 20px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 0;
    z-index: 1001;
  }

  .burger {
    font-size: 24px;
    cursor: pointer;
  }

  .dashboard-title {
    font-size: 20px;
    font-weight: bold;
    color: #333;
  }

  .search-bar {
    padding: 5px 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
  }

  .grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    padding: 20px;
  }

  .card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .status {
    font-size: 14px;
  }

  .status.red {
    color: red;
  }

  .weekly-chart {
    background: #fff;
    padding: 20px;
    margin: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  th, td {
    padding: 10px;
    border-bottom: 1px solid #eee;
    text-align: left;
  }

  th {
    background: #444;
    color: white;
  }

  .delete-btn {
    background: red;
    color: #fff;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
  }

  .logout-button {
    margin: 20px;
    text-align: right;
  }

  .logout-button button {
    padding: 10px 20px;
    background: green;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  }

  form input {
    margin-right: 10px;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
  }

  form button {
    padding: 8px 16px;
    background: #4b7c67;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  }
</style>
</head>
<body>
  <div class="sidebar" id="sidebar">
    <h3>NBSC<br>MediSync</h3>
    <a href="#">Dashboard</a>
    <a href="#">Medicine Inventory</a>
    <a href="#">Prescription Management</a>
    <a href="#">Orders & Supplier</a>
    <a href="#">Reports & Analytics</a>
    <a href="#">Settings</a>
  </div>

  <div class="main-content" id="main">
    <div class="header">
      <span class="burger" onclick="toggleSidebar()">&#9776;</span>
      <h2>Dashboard</h2>
      <input type="text" class="search-bar" placeholder="Search">
    </div>

    <?php
// Ensure $prescriptions is defined before this
$patientNames = array_map(fn($pres) => $pres[0], $prescriptions ?? []);
$totalPatients = count(array_unique($patientNames));
?>

<div class="grid">
  <div class="card">
    <h3>Total Patients</h3>
    <p style="font-size:24px;"><?= $totalPatients ?></p>
    <p class="status">🔼 1% from last month</p>
  </div>
      <div class="card">
        <h3>Today's Appointments</h3>
        <p style="font-size:24px;"><?= count($appointmentsToday) ?></p>
        <p class="status red">🔽 2% from yesterday</p> <!-- Optional: Add dynamic trend logic -->
      </div>

      <div class="card">
        <h3>Pending Prescriptions</h3>
        <p style="font-size:24px;">0</p>
        <p class="status" style="color:orange;">⚠️ Needs attention</p>
      </div>
      <div class="card">
  <h3>Revenue</h3>
  <p id="revenueDisplay" style="font-size:24px;">₱<?= number_format($revenue, 2) ?></p>
  <p class="status">💰 Updated real-time</p>
</div>

    




    <div class="card">
  <h3>New Patients This Week</h3>
  <p id="newPatientsCount" style="font-size:24px;"><?= count($prescriptions) ?></p>
  <p class="status">🆕 Growing steadily</p>
</div>


<!-- Additional KPIs -->

<div class="card">
    <h3>Total Medicine Inventory</h3>
    <p style="font-size:24px;">0</p>
    <p class="status">🧪 Inventory monitored</p>
  </div>
  <div class="card">
    <h3>Out of Stock</h3>
    <p style="font-size:24px;">0</p>
    <p class="status red">❗ Reorder Needed</p>
  </div>


  <div class="card">
    <h3>Supplier Orders This Month</h3>
    <p style="font-size:24px;">0</p>
    <p class="status">📦 All received</p>
  </div>
</div>







    <div class="weekly-chart">
      <h3>Weekly Appointments</h3>
      <canvas id="weeklyAppointmentsChart" style="width:100%; height:200px;"></canvas>
    </div>

    <div style="padding:20px;">
      <h3>Recent Prescriptions</h3>
      <form method="POST" style="margin-bottom:20px;">
        <input type="text" name="patient" placeholder="Patient" required>
        <input type="text" name="medicine" placeholder="Medicine" required>
        <input type="text" name="dosage" placeholder="Dosage" required>
        <button type="submit">Add Prescription</button>
      </form>

      <table id="prescriptionsTable">
        <tr>
          <th>Patient</th>
          <th>Medicine</th>
          <th>Dosage</th>
          <th>Action</th>
        </tr>
        <?php foreach ($prescriptions as $idx => $pres): ?>
          <tr data-index="<?= $idx ?>">
            <td><?= htmlspecialchars($pres[0]) ?></td>
            <td><?= htmlspecialchars($pres[1]) ?></td>
            <td><?= htmlspecialchars($pres[2]) ?></td>
            <td>
              <button class="delete-btn" onclick="deletePrescription(<?= $idx ?>)">Delete</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>

    <div class="logout-button">
      <button onclick="location.href='logout.php'">Exit</button>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('hidden');
      document.getElementById('main').classList.toggle('full');
    }

    // Chart.js setup
    const ctx = document.getElementById('weeklyAppointmentsChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        datasets: [{ label:'Appointments', data:[20,18,22,25,24,15,10], backgroundColor:'#4b7c67' }]
      },
      options: { responsive:true, plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true, ticks:{ stepSize:5 }}}}
    });

 // AJAX delete
function deletePrescription(idx) {
  const row = document.querySelector(`tr[data-index="${idx}"]`);

  fetch('delete_prescription.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'delete_index=' + encodeURIComponent(idx)
  })
  .then(res => res.json())
  .then(json => {
    if (json.success) {
      row.remove(); // Remove the prescription row from the table

      // Optionally, if you're calculating revenue, update the total revenue
      let total = 0;
      document.querySelectorAll('#prescriptionsTable tr[data-price]').forEach(row => {
        total += parseFloat(row.getAttribute('data-price')) || 0;
      });
      
      const revenueEl = document.getElementById('revenueDisplay'); // Assuming you have a revenue display element
      revenueEl.textContent = `₱${total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

      // Decrease the New Patients count after deleting a prescription
      const countEl = document.getElementById('newPatientsCount');
      countEl.textContent = Math.max(0, parseInt(countEl.textContent) - 1); // Decrease patient count
    } else {
      alert('Failed to delete');
    }
  })
  .catch(() => alert('Error deleting'));
}




// Real-time add prescription and update card
document.querySelector('form').addEventListener('submit', function(e) {
  e.preventDefault();

  const formData = new FormData(this);

  fetch('', { // Make a POST request to add a new prescription
    method: 'POST',
    body: formData
  })
  .then(() => {
    const table = document.getElementById('prescriptionsTable');
    const newRow = table.insertRow(1); // Insert a new row after the header

    const patient = formData.get('patient');
    const medicine = formData.get('medicine');
    const dosage = formData.get('dosage');
    const index = table.rows.length - 2; // Approximate new index for the row

    newRow.setAttribute('data-index', index);
    newRow.innerHTML = `
      <td>${patient}</td>
      <td>${medicine}</td>
      <td>${dosage}</td>
      <td><button class="delete-btn" onclick="deletePrescription(${index})">Delete</button></td>
    `;

    // Update New Patients Count
    const countEl = document.getElementById('newPatientsCount');
    countEl.textContent = parseInt(countEl.textContent) + 1; // Increment the count

    // Optionally, update Total Patients count (if the patient is unique)
    const totalPatientsCountEl = document.getElementById('totalPatientsCount');
    totalPatientsCountEl.textContent = parseInt(totalPatientsCountEl.textContent) + 1;

    this.reset(); // Clear form fields after submission
  })
  .catch(() => alert("Failed to add prescription"));
});





  </script>
</body>
</html>
