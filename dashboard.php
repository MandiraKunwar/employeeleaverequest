<?php
// --- Database Connection Configuration ---
// Make sure to update these with your actual database credentials.
// For a default XAMPP setup, the username is 'root' and the password is an empty string.
$servername = "localhost";
$username = "root"; // Your database username
$password = "";     // Your database password
$dbname = "admin";  // Your database name (based on the provided screenshot)

// Create a new database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the connection was successful. If not, stop the script and show the error.
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ----------------------------------------------------
// Step 1: Query the database for leave application counts
// Using the correct table name 'leave_applications'
// ----------------------------------------------------

// Total leaves
$sql_total = "SELECT COUNT(*) AS total_leaves FROM leave_applications";
$result_total = $conn->query($sql_total);
$total_leaves = $result_total->fetch_assoc()['total_leaves'];

// Approved leaves (Using 'Approved' as the status string)
$sql_approved = "SELECT COUNT(*) AS approved_leaves FROM leave_applications WHERE Status = 'Approved'";
$result_approved = $conn->query($sql_approved);
$approved_leaves = $result_approved->fetch_assoc()['approved_leaves'];

// Pending leaves (Using 'Pending' as the status string)
$sql_pending = "SELECT COUNT(*) AS pending_leaves FROM leave_applications WHERE Status = 'Pending'";
$result_pending = $conn->query($sql_pending);
$pending_leaves = $result_pending->fetch_assoc()['pending_leaves'];

// Rejected leaves (Using 'Rejected' as the status string)
$sql_rejected = "SELECT COUNT(*) AS rejected_leaves FROM leave_applications WHERE Status = 'Rejected'";
$result_rejected = $conn->query($sql_rejected);
$rejected_leaves = $result_rejected->fetch_assoc()['rejected_leaves'];

// --------------------------------------------------------------------
// Step 2: Query the database for leave type and department counts
// Using the correct table names 'leave_types' and 'departments'
// --------------------------------------------------------------------

// Total leave types
$sql_leave_types = "SELECT COUNT(*) AS total_types FROM leave_types";
$result_leave_types = $conn->query($sql_leave_types);
$total_leave_types = $result_leave_types->fetch_assoc()['total_types'];

// Total departments
$sql_departments = "SELECT COUNT(*) AS total_departments FROM departments";
$result_departments = $conn->query($sql_departments);
$total_departments = $result_departments->fetch_assoc()['total_departments'];

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="stylee.css">
    <style>
        /* Styles for the summary cards */
        .summary-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 50px;
        }
        .summary-card {
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin: 15px;
            padding: 20px;
            text-align: center;
            width: 200px;
            transition: transform 0.3s ease;
        }
        .summary-card:hover {
            transform: translateY(-5px);
        }
        .summary-card h3 {
            margin-top: 0;
            color: #333;
        }
        .summary-card .count {
            font-size: 3em;
            font-weight: bold;
        }
        .total .count { color: #28a745; }
        .approved .count { color: #17a2b8; }
        .pending .count { color: #ffc107; }
        .rejected .count { color: #dc3545; }
        .type .count { color: #6f42c1; } /* New color for leave types */
        .department .count { color: #fd7e14; } /* New color for departments */
    </style>
</head>
<body>
    <input type="checkbox" id="checkbox">
    <header class="header">
        <h2 class="u-name">
            <span class="sm-part">SM</span> <span class="company-part">COMPANY</span>
            <label for="checkbox">
                <i id="navbtn" class="fa fa-bars" aria-hidden="true"></i>
            </label>
        </h2>
    </header>
    <div class="body">
        <nav class="side-bar">
            <!-- Admin Navigation Bar -->
            <ul>
                <li>
                    <a href="dashboard.php">
                        <i class="fa fa-tachometer" aria-hidden="true"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li>
                    <a href="type.php">
                        <i class="fa fa-tasks" aria-hidden="true"></i>
                        <span>Manage Leave Types</span>
                    </a>
                </li>

                <li>
                    <a href="manageemployee.php">
                        <i class="fa fa-users" aria-hidden="true"></i>
                        <span>Manage Employee</span>
                    </a>
                </li>

                <li>
                    <a href="status.php">
                        <i class="fa fa-tasks" aria-hidden="true"></i>
                        <span>Manage leave status</span>
                    </a>
                </li>

                <li>
                    <a href="managedepartment.php">
                        <i class="fa fa-tasks" aria-hidden="true"></i>
                        <span>Manage Department</span>
                    </a>
                </li>

                <li>
                    <a href="home.html">
                        <i class="fa fa-sign-out" aria-hidden="true"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
        <section class="section-1">
            <h1 style="font-size: 40px; font-weight: bold; text-align: center; color: green; margin-top: 50px;">Welcome TO Admin Dashboard</h1>

            <!-- Summary of Leave Applications -->
            <div class="summary-container">
                <div class="summary-card total">
                    <h3>Total Leaves</h3>
                    <div class="count"><?php echo $total_leaves; ?></div>
                </div>
                <div class="summary-card approved">
                    <h3>Approved Leaves</h3>
                    <div class="count"><?php echo $approved_leaves; ?></div>
                </div>
                <div class="summary-card pending">
                    <h3>Pending Leaves</h3>
                    <div class="count"><?php echo $pending_leaves; ?></div>
                </div>
                <div class="summary-card rejected">
                    <h3>Rejected Leaves</h3>
                    <div class="count"><?php echo $rejected_leaves; ?></div>
                </div>
                
                <!-- New sections for Leave Types and Departments -->
                <div class="summary-card type">
                    <h3>Total Leave Types</h3>
                    <div class="count"><?php echo $total_leave_types; ?></div>
                </div>
                <div class="summary-card department">
                    <h3>Total Departments</h3>
                    <div class="count"><?php echo $total_departments; ?></div>
                </div>
            </div>
            <!-- End of Summary -->
        </section>
    </div>
</body>
</html>
