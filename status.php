<?php
// Database connection details
$servername = "localhost";
$username_db = "root"; // Your database username
$password_db = "";     // Your database password
$dbname = "admin";     // Your database name, as you previously mentioned

// Create connection
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = ''; // Variable to store success/error messages

// Handle POST requests for updating leave status
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'update_status') {
        $request_id = $_POST['request_id'] ?? '';
        $new_status = $_POST['new_status'] ?? '';

        // Validate inputs
        if (empty($request_id) || !is_numeric($request_id)) {
            $message = "Error: Invalid request ID.";
        } elseif (!in_array($new_status, ['Pending', 'Approved', 'Rejected'])) {
            $message = "Error: Invalid status value.";
        } else {
            // Prepare and execute the update statement for the 'leave_applications' table
            $stmt = $conn->prepare("UPDATE leave_applications SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $request_id);

            if ($stmt->execute()) {
                $message = "Leave request status updated successfully to '" . htmlspecialchars($new_status) . "'!";
            } else {
                $message = "Error updating leave request status: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Manage Leave Status</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="stylee.css"> <script src="https://cdn.tailwindcss.com"></script> <link rel="stylesheet" href="status.css">
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
                        <span>Manage Employees</span>
                    </a>
                </li>
                <li>
                    <a href="status.php"> <i class="fa fa-tasks" aria-hidden="true"></i>
                        <span>Manage Leave Status</span>
                    </a>
                </li>
                <li>
                    <a href="managedepartment.php">
                        <i class="fa fa-building" aria-hidden="true"></i>
                        <span>Manage Department</span>
                    </a>
               
                <li>
                    <a href="home.html">
                        <i class="fa fa-sign-out" aria-hidden="true"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
        <section class="section-1">
            <div class="bg-white rounded-lg shadow-xl p-8 w-full">
                <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Manage Leave Status</h2>

                <?php if (!empty($message)): ?>
                    <div class="mb-4 p-3 rounded-md <?php echo strpos($message, 'Error') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?> text-center font-medium">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="p-6 border border-gray-200 rounded-lg">
                    <h3 class="text-2xl font-semibold text-gray-700 mb-4">All Leave Requests</h3>
                    <div class="overflow-x-auto table-container">
                        <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700 uppercase text-sm leading-normal">
                                    <th class="py-3 px-6 text-left">S.N.</th>
                                    <th class="py-3 px-6 text-left">Employee Name</th>
                                    <th class="py-3 px-6 text-left">Leave Type</th>
                                    <th class="py-3 px-6 text-left">Start Date</th>
                                    <th class="py-3 px-6 text-left">End Date</th>
                                    <th class="py-3 px-6 text-left">Reason</th>
                                    <th class="py-3 px-6 text-center">Status</th>
                                    <th class="py-3 px-6 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm font-light">
                                <?php
                                // Display existing leave requests
                                // Corrected the JOIN queries to match your table names: 'leave_applications', 'employees', and 'leave_types'
                                $sql = "SELECT la.id, e.full_name AS employee_name, lt.leave_type AS leave_type_name, 
                                                la.start_date, la.end_date, la.reason, la.status
                                        FROM leave_applications la
                                        JOIN employees e ON la.employee_id = e.id
                                        JOIN leave_types lt ON la.leave_type_id = lt.id
                                        ORDER BY la.start_date DESC";
                                $result = $conn->query($sql);

                                if ($result->num_rows > 0) {
                                    $serial_number = 1;
                                    while ($row = $result->fetch_assoc()) {
                                        $status_class = '';
                                        if ($row['status'] == 'Pending') {
                                            $status_class = 'pending';
                                        } elseif ($row['status'] == 'Approved') {
                                            $status_class = 'approved';
                                        } elseif ($row['status'] == 'Rejected') {
                                            $status_class = 'rejected';
                                        }

                                        echo "<tr class='border-b border-gray-200 hover:bg-gray-50'>";
                                        echo "<td class='py-3 px-6 text-left whitespace-nowrap'>" . $serial_number++ . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["employee_name"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["leave_type_name"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["start_date"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["end_date"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["reason"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-center'>";
                                        echo "<span class='font-semibold " . $status_class . "'>" . htmlspecialchars($row["status"]) . "</span>";
                                        echo "</td>";
                                        echo "<td class='py-3 px-6 text-center'>";
                                        echo "<form action='status.php' method='post' class='inline-block' onsubmit='return confirmStatusChange(\"" . htmlspecialchars($row["employee_name"]) . "\", this.elements[\"new_status\"].value);'>";
                                        echo "<input type='hidden' name='action' value='update_status'>";
                                        echo "<input type='hidden' name='request_id' value='" . $row["id"] . "'>";
                                        echo "<select name='new_status' class='status-select " . $status_class . " mr-2'>";
                                        echo "<option value='Pending'" . ($row['status'] == 'Pending' ? ' selected' : '') . ">Pending</option>";
                                        echo "<option value='Approved'" . ($row['status'] == 'Approved' ? ' selected' : '') . ">Approved</option>";
                                        echo "<option value='Rejected'" . ($row['status'] == 'Rejected' ? ' selected' : '') . ">Rejected</option>";
                                        echo "</select>";
                                        echo "<button type='submit' class='px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold rounded-md'>Update</button>";
                                        echo "</form>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' class='py-3 px-6 text-center text-gray-600'>No leave requests found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            </section>
    </div>

    <script>
        // Simple client-side script for confirmation for status change
        function confirmStatusChange(employeeName, newStatus) {
            return confirm(`Are you sure you want to change the leave status for ${employeeName} to ${newStatus}?`);
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>