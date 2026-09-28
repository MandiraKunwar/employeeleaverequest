<?php
session_start();

// Redirect to login if employee is not logged in.
if (!isset($_SESSION['employee_id'])) {
    header('Location: logempSQL.php');
    exit();
}

$servername = "localhost";
$dbname = "admin";
$username_db = "root";
$password_db = "";

// Create connection
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$employee_id = $_SESSION['employee_id'];
$message = '';

// Handle POST requests for Add, Update, Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $leave_type_id = $_POST['leave_type_id'] ?? '';
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';
            $reason = trim($_POST['reason'] ?? '');

            if (empty($leave_type_id) || empty($start_date) || empty($end_date) || empty($reason)) {
                $message = "Error: All fields are required.";
            } elseif ($start_date > $end_date) {
                $message = "Error: The start date cannot be after the end date.";
            } else {
                $stmt = $conn->prepare("INSERT INTO leave_applications (employee_id, leave_type_id, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
                $stmt->bind_param("iisss", $employee_id, $leave_type_id, $start_date, $end_date, $reason);

                if ($stmt->execute()) {
                    $message = "Leave application submitted successfully!";
                } else {
                    $message = "Error submitting leave: " . $stmt->error;
                }
                $stmt->close();
            }
            break;

        case 'update':
            $application_id = $_POST['application_id'] ?? '';
            $leave_type_id = $_POST['leave_type_id'] ?? '';
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';
            $reason = trim($_POST['reason'] ?? '');

            if (empty($application_id) || empty($leave_type_id) || empty($start_date) || empty($end_date) || empty($reason)) {
                $message = "Error: All fields are required for update.";
            } elseif ($start_date > $end_date) {
                $message = "Error: The start date cannot be after the end date.";
            } else {
                // Only allow updates on 'Pending' applications
                $stmt_check = $conn->prepare("SELECT status FROM leave_applications WHERE id = ? AND employee_id = ?");
                $stmt_check->bind_param("ii", $application_id, $employee_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                $row = $result_check->fetch_assoc();
                $stmt_check->close();

                if ($row && $row['status'] === 'Pending') {
                    $stmt = $conn->prepare("UPDATE leave_applications SET leave_type_id = ?, start_date = ?, end_date = ?, reason = ? WHERE id = ? AND employee_id = ?");
                    $stmt->bind_param("isssii", $leave_type_id, $start_date, $end_date, $reason, $application_id, $employee_id);
                    
                    if ($stmt->execute()) {
                        $message = "Leave application updated successfully!";
                    } else {
                        $message = "Error updating leave: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $message = "Error: Cannot update a leave application that is not pending.";
                }
            }
            break;

        case 'delete':
            $application_id = $_POST['application_id'] ?? '';
            
            // Only allow deletion of 'Pending' applications
            $stmt_check = $conn->prepare("SELECT status FROM leave_applications WHERE id = ? AND employee_id = ?");
            $stmt_check->bind_param("ii", $application_id, $employee_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $row = $result_check->fetch_assoc();
            $stmt_check->close();

            if ($row && $row['status'] === 'Pending') {
                $stmt = $conn->prepare("DELETE FROM leave_applications WHERE id = ? AND employee_id = ?");
                $stmt->bind_param("ii", $application_id, $employee_id);

                if ($stmt->execute()) {
                    $message = "Leave application deleted successfully!";
                } else {
                    $message = "Error deleting leave: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "Error: Cannot delete a leave application that is not pending.";
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="stylee.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f3f4f6;
        }
    </style>
</head>
<body>

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
                    <a href="dash.php">
                        <i class="fa fa-tachometer" aria-hidden="true"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="applyleave.php">
                        <i class="fa fa-tasks" aria-hidden="true"></i>
                        <span>Apply for Leave</span>
                    </a>
                </li>
                <li>
                    <a href="employeeprofile.php">
                        <i class="fa fa-user" aria-hidden="true"></i>
                        <span>Profile</span>
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
        <section class="section-1 p-8">
            <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-xl p-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Apply for Leave</h1>

                <?php if (!empty($message)): ?>
                    <div class="mb-4 p-4 rounded-lg text-center font-medium
                        <?php echo strpos($message, 'Error') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div id="application-form-container" class="mb-8 p-6 border border-gray-200 rounded-lg shadow-sm">
                    <h2 id="form-title" class="text-2xl font-semibold text-gray-700 mb-4 text-center">New Leave Application</h2>
                    <form id="leave-form" action="applyleave.php" method="post" class="space-y-4">
                        <input type="hidden" name="action" id="form-action" value="add">
                        <input type="hidden" name="application_id" id="application-id" value="">

                        <div>
                            <label for="leave_type_id" class="block text-gray-700 text-sm font-semibold mb-2">Leave Type:</label>
                            <select id="leave_type_id" name="leave_type_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select a Leave Type</option>
                                <?php
                                // Fetch leave types from the database to populate the dropdown
                                $sql_types = "SELECT id, leave_type FROM leave_types ORDER BY leave_type";
                                $result_types = $conn->query($sql_types);
                                if ($result_types->num_rows > 0) {
                                    while($row = $result_types->fetch_assoc()) {
                                        echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['leave_type']) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="flex space-x-4">
                            <div class="w-1/2">
                                <label for="start_date" class="block text-gray-700 text-sm font-semibold mb-2">Start Date:</label>
                                <input type="date" id="start_date" name="start_date" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div class="w-1/2">
                                <label for="end_date" class="block text-gray-700 text-sm font-semibold mb-2">End Date:</label>
                                <input type="date" id="end_date" name="end_date" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>

                        <div>
                            <label for="reason" class="block text-gray-700 text-sm font-semibold mb-2">Reason for Leave:</label>
                            <textarea id="reason" name="reason" rows="4" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        </div>
                        
                        <div class="flex justify-end space-x-4">
                            <button type="submit" id="submit-button" class="w-full py-3 px-6 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105">
                                Submit Leave
                            </button>
                            <button type="button" id="cancel-button" onclick="resetForm()" class="w-full py-3 px-6 bg-gray-400 hover:bg-gray-500 text-white font-semibold rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105 hidden">
                                Cancel Update
                            </button>
                        </div>
                    </form>
                </div>

                <div class="p-6 border border-gray-200 rounded-lg shadow-sm">
                    <h2 class="text-2xl font-semibold text-gray-700 mb-4 text-center">My Leave Applications</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700 uppercase text-sm leading-normal">
                                    <th class="py-3 px-6 text-left">Leave Type</th>
                                    <th class="py-3 px-6 text-left">Start Date</th>
                                    <th class="py-3 px-6 text-left">End Date</th>
                                    <th class="py-3 px-6 text-left">Status</th>
                                    <th class="py-3 px-6 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm font-light">
                                <?php
                                // Fetch and display leave applications for the current employee
                               // Corrected SQL query to use an existing column
$sql = "SELECT 
            la.id, 
            lt.leave_type, 
            la.start_date, 
            la.end_date, 
            la.reason, 
            la.status 
        FROM 
            leave_applications la
        JOIN 
            leave_types lt ON la.leave_type_id = lt.id
        WHERE 
            la.employee_id = ?
        ORDER BY 
            la.start_date DESC"; // Using start_date as the sorting column
                                
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("i", $employee_id);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $status_class = '';
                                        if ($row['status'] === 'Approved') {
                                            $status_class = 'bg-green-100 text-green-800';
                                        } elseif ($row['status'] === 'Rejected') {
                                            $status_class = 'bg-red-100 text-red-800';
                                        } else {
                                            $status_class = 'bg-yellow-100 text-yellow-800';
                                        }

                                        echo "<tr class='border-b border-gray-200 hover:bg-gray-50'>";
                                        echo "<td class='py-3 px-6 text-left whitespace-nowrap'>" . htmlspecialchars($row["leave_type"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["start_date"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["end_date"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-center'><span class='px-2 py-1 font-semibold rounded-full " . $status_class . "'>" . htmlspecialchars($row["status"]) . "</span></td>";
                                        echo "<td class='py-3 px-6 text-center'>";

                                        // Only show action buttons if the status is 'Pending'
                                        if ($row['status'] === 'Pending') {
                                            echo "<div class='flex items-center justify-center space-x-2'>";
                                            // Edit button
                                            echo "<button type='button' onclick='populateFormForEdit(" . json_encode($row) . ")' class='px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold rounded-md'>Edit</button>";
                                            // Delete form
                                            echo "<form action='apply_leave.php' method='post' onsubmit='return confirmDelete();'>";
                                            echo "<input type='hidden' name='action' value='delete'>";
                                            echo "<input type='hidden' name='application_id' value='" . $row["id"] . "'>";
                                            echo "<button type='submit' class='px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded-md'>Delete</button>";
                                            echo "</form>";
                                            echo "</div>";
                                        } else {
                                            echo "<span class='text-gray-400'>No actions</span>";
                                        }
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='py-3 px-6 text-center text-gray-600'>You have not submitted any leave applications yet.</td></tr>";
                                }
                                $stmt->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <script>
        // Function to populate the form for editing
        function populateFormForEdit(application) {
            document.getElementById('form-title').innerText = 'Update Leave Application';
            document.getElementById('application-id').value = application.id;
            // Assumes leave_type_id is in the fetched data
            document.getElementById('leave_type_id').value = application.leave_type_id; 
            document.getElementById('start_date').value = application.start_date;
            document.getElementById('end_date').value = application.end_date;
            document.getElementById('reason').value = application.reason;
            
            document.getElementById('form-action').value = 'update';
            document.getElementById('submit-button').innerText = 'Update Leave';
            document.getElementById('cancel-button').classList.remove('hidden');

            // Scroll to the form
            document.getElementById('application-form-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Function to reset the form to "Add New" mode
        function resetForm() {
            document.getElementById('form-title').innerText = 'New Leave Application';
            document.getElementById('leave-form').reset();
            document.getElementById('application-id').value = '';
            document.getElementById('form-action').value = 'add';
            document.getElementById('submit-button').innerText = 'Submit Leave';
            document.getElementById('cancel-button').classList.add('hidden');
        }

        function confirmDelete() {
            return confirm("Are you sure you want to delete this leave application?");
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>