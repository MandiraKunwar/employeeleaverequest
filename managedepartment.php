<?php
// Database connection details
$servername = "localhost";
$username_db = "root"; // Your database username
$password_db = "";     // Your database password
$dbname = "admin"; // Corrected database name to match your screenshot

// Create connection
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = ''; // Variable to store success/error messages

// Function to check for duplicates
function isDuplicate($conn, $table, $column, $value, $exclude_id = null) {
    $sql = "SELECT COUNT(*) FROM $table WHERE $column = ?";
    if ($exclude_id !== null) {
        $sql .= " AND id != ?";
    }
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        // Handle prepare error, e.g., log it and return true to prevent further issues
        error_log("Failed to prepare statement for isDuplicate: " . $conn->error);
        return true;
    }
    if ($exclude_id !== null) {
        $stmt->bind_param("si", $value, $exclude_id);
    } else {
        $stmt->bind_param("s", $value);
    }
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0;
}
// Handle POST requests for Add, Update, Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $department_name = trim($_POST['department_name'] ?? '');
            $department_short_name = trim($_POST['department_short_name'] ?? '');
            $department_code = trim($_POST['department_code'] ?? '');

            if (empty($department_name)) {
                $message = "Department Name cannot be empty.";
            } elseif (!preg_match("/^[a-zA-Z\s]+$/", $department_name)) { // Added validation for letters and spaces
                $message = "Department Name must contain only letters and spaces.";
            } elseif (isDuplicate($conn, 'departments', 'department_name', $department_name)) {
                $message = "Department Name already exists.";
            } elseif (!empty($department_short_name) && isDuplicate($conn, 'departments', 'department_short_name', $department_short_name)) {
                $message = "Department Short Name already exists.";
            } elseif (!empty($department_code) && isDuplicate($conn, 'departments', 'department_code', $department_code)) {
                $message = "Department Code already exists.";
            } else {
                $stmt = $conn->prepare("INSERT INTO departments (department_name, department_short_name, department_code) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $department_name, $department_short_name, $department_code);

                if ($stmt->execute()) {
                    $message = "Department added successfully!";
                } else {
                    $message = "Error adding department: " . $stmt->error;
                }
                $stmt->close();
            }
            break;

        case 'update':
            $id = $_POST['id_to_update'] ?? ''; // Get ID from the hidden field in the main form
            $department_name = trim($_POST['department_name'] ?? '');
            $department_short_name = trim($_POST['department_short_name'] ?? '');
            $department_code = trim($_POST['department_code'] ?? '');

            if (empty($id)) {
                $message = "Error: Department ID for update is missing.";
                break;
            }
            if (empty($department_name)) {
                $message = "Department Name cannot be empty.";
            } elseif (!preg_match("/^[a-zA-Z\s]+$/", $department_name)) { // Added validation for letters and spaces
                $message = "Department Name must contain only letters and spaces.";
            } elseif (isDuplicate($conn, 'departments', 'department_name', $department_name, $id)) {
                $message = "Department Name already exists.";
            } elseif (!empty($department_short_name) && isDuplicate($conn, 'departments', 'department_short_name', $department_short_name, $id)) {
                $message = "Department Short Name already exists.";
            } elseif (!empty($department_code) && isDuplicate($conn, 'departments', 'department_code', $department_code, $id)) {
                $message = "Department Code already exists.";
            } else {
                $stmt = $conn->prepare("UPDATE departments SET department_name = ?, department_short_name = ?, department_code = ? WHERE id = ?");
                $stmt->bind_param("sssi", $department_name, $department_short_name, $department_code, $id);

                if ($stmt->execute()) {
                    $message = "Department updated successfully!";
                } else {
                    $message = "Error updating department: " . $stmt->error;
                }
                $stmt->close();
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? '';

            $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $message = "Department deleted successfully!";
            } else {
                $message = "Error deleting department: " . $stmt->error;
            }
            $stmt->close();
            break;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Manage Departments</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="stylee.css"> <!-- Your existing dashboard CSS -->
    <script src="https://cdn.tailwindcss.com"></script> <!-- Tailwind CSS for department management styles -->
    <link rel="stylesheet" href="dep.css">
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
                    <a href="managedepartment.php"> <!-- Link to itself -->
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
            <!-- Department Management Content Starts Here -->
            <div class="bg-white rounded-lg shadow-xl p-8 max-w-4xl w-full">
                <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Manage Departments</h2>

                <?php if (!empty($message)): ?>
                    <div class="mb-4 p-3 rounded-md <?php echo strpos($message, 'Error') !== false || strpos($message, 'cannot be empty') !== false || strpos($message, 'already exists') !== false || strpos($message, 'must contain only letters and spaces') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?> text-center font-medium">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Add/Update Department Form -->
                <div class="mb-8 p-6 border border-gray-200 rounded-lg">
                    <h3 id="form-title" class="text-2xl font-semibold text-gray-700 mb-4 text-center">Add New Department</h3>
                    <form id="department-form" action="managedepartment.php" method="post" class="space-y-4">
                        <input type="hidden" name="action" id="form-action" value="add">
                        <input type="hidden" name="id_to_update" id="id_to_update" value="">
                        <div>
                            <label for="dept_name" class="block text-gray-700 text-sm font-semibold mb-2 text-left">Department Name:</label>
                            <input type="text" id="dept_name" name="department_name" placeholder="e.g., Human Resources" required
                                   pattern="^[a-zA-Z\s]+$" title="Department Name must contain only letters and spaces."
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                        </div>
                        <div>
                            <label for="dept_short_name" class="block text-gray-700 text-sm font-semibold mb-2 text-left">Short Name:</label>
                            <input type="text" id="dept_short_name" name="department_short_name" placeholder="e.g., HR"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                        </div>
                        <div>
                            <label for="dept_code" class="block text-gray-700 text-sm font-semibold mb-2 text-left">Department Code:</label>
                            <input type="text" id="dept_code" name="department_code" placeholder="e.g., DPT001"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" id="submit-button" class="w-full py-3 px-6 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-75">
                                Add Department
                            </button>
                            <button type="button" id="cancel-button" onclick="resetForm()" class="w-full py-3 px-6 bg-gray-400 hover:bg-gray-500 text-white font-semibold rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-opacity-75 hidden">
                                Cancel Update
                            </button>
                        </div>
                    </form>
                </div>

                <!-- List Existing Departments (for Update/Delete) -->
                <div class="p-6 border border-gray-200 rounded-lg">
                    <h3 class="text-2xl font-semibold text-gray-700 mb-4">Existing Departments</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700 uppercase text-sm leading-normal">
                                    <th class="py-3 px-6 text-left">S.N.</th>
                                    <th class="py-3 px-6 text-left">Department Name</th>
                                    <th class="py-3 px-6 text-left">Short Name</th>
                                    <th class="py-3 px-6 text-left">Code</th>
                                    <th class="py-3 px-6 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm font-light">
                                <?php
                                // Display existing departments
                                $sql = "SELECT id, department_name, department_short_name, department_code FROM departments ORDER BY department_name";
                                $result = $conn->query($sql);

                                if ($result->num_rows > 0) {
                                    $serial_number = 1;
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr class='border-b border-gray-200 hover:bg-gray-50'>";
                                        echo "<td class='py-3 px-6 text-left whitespace-nowrap'>" . $serial_number++ . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["department_name"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["department_short_name"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["department_code"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-center'>";
                                        echo "<div class='flex items-center justify-center space-x-2'>";
                                        // Edit button to populate the form
                                        echo "<button type='button' onclick='populateFormForEdit(" . $row["id"] . ", \"" . htmlspecialchars($row["department_name"], ENT_QUOTES) . "\", \"" . htmlspecialchars($row["department_short_name"], ENT_QUOTES) . "\", \"" . htmlspecialchars($row["department_code"], ENT_QUOTES) . "\")' class='px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold rounded-md'>Edit</button>";
                                        // Delete Form
                                        echo "<form action='managedepartment.php' method='post' onsubmit='return confirmDelete(\"" . htmlspecialchars($row["department_name"]) . "\");'>";
                                        echo "<input type='hidden' name='action' value='delete'>";
                                        echo "<input type='hidden' name='id' value='" . $row["id"] . "'>";
                                        echo "<button type='submit' class='px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded-md'>Delete</button>";
                                        echo "</form>";
                                        echo "</div>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='py-3 px-6 text-center text-gray-600'>No departments found. Add a new one above!</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Department Management Content Ends Here -->
        </section>
    </div>

    <script>
        // Function to populate the form for editing
        function populateFormForEdit(id, name, shortName, code) {
            document.getElementById('form-title').innerText = 'Update Department';
            document.getElementById('dept_name').value = name;
            document.getElementById('dept_short_name').value = shortName;
            document.getElementById('dept_code').value = code;
            document.getElementById('id_to_update').value = id;
            document.getElementById('form-action').value = 'update';
            document.getElementById('submit-button').innerText = 'Update Department';
            document.getElementById('submit-button').classList.remove('bg-green-600', 'hover:bg-green-700');
            document.getElementById('submit-button').classList.add('bg-blue-600', 'hover:bg-blue-700');
            document.getElementById('cancel-button').classList.remove('hidden');

            // Scroll to the top of the form for better UX
            document.getElementById('form-title').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Function to reset the form to "Add New Department" mode
        function resetForm() {
            document.getElementById('form-title').innerText = 'Add New Department';
            document.getElementById('department-form').reset(); // Clears all form fields
            document.getElementById('id_to_update').value = '';
            document.getElementById('form-action').value = 'add';
            document.getElementById('submit-button').innerText = 'Add Department';
            document.getElementById('submit-button').classList.remove('bg-blue-600', 'hover:bg-blue-700');
            document.getElementById('submit-button').classList.add('bg-green-600', 'hover:bg-green-700');
            document.getElementById('cancel-button').classList.add('hidden');
        }

        // Simple client-side script for confirmation for delete
        function confirmDelete(deptName) {
            return confirm(`Are you sure you want to delete department: ${deptName}?`);
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>
