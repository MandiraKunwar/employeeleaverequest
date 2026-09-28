<?php
// Database connection details
$servername = "localhost";
$username_db = "root"; // Your database username
$password_db = "";     // Your database password
$dbname = "admin"; // Your database name

// Create connection
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = ''; // Variable to store success/error messages

// Handle POST requests for Add, Update, Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $leave_type = trim($_POST['leave_type'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $default_entitlement = $_POST['default_entitlement'] ?? 0;

            if (empty($leave_type) || $default_entitlement < 0) {
                $message = "Leave type and a non-negative entitlement are required.";
            } else {
                // Check for duplicate leave type
                $stmt_check = $conn->prepare("SELECT COUNT(*) FROM leave_types WHERE leave_type = ?");
                $stmt_check->bind_param("s", $leave_type);
                $stmt_check->execute();
                $stmt_check->bind_result($count);
                $stmt_check->fetch();
                $stmt_check->close();

                if ($count > 0) {
                    $message = "Error: A leave type with this name already exists.";
                } else {
                    $stmt = $conn->prepare("INSERT INTO leave_types (leave_type, description, default_entitlement) VALUES (?, ?, ?)");
                    $stmt->bind_param("ssi", $leave_type, $description, $default_entitlement);

                    if ($stmt->execute()) {
                        $message = "Leave type added successfully!";
                    } else {
                        $message = "Error adding leave type: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
            break;

        case 'update':
            $id = $_POST['id_to_update'] ?? '';
            $leave_type = trim($_POST['leave_type'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $default_entitlement = $_POST['default_entitlement'] ?? 0;

            if (empty($leave_type) || $default_entitlement < 0 || empty($id)) {
                $message = "Leave type, entitlement, and ID are required for update.";
            } else {
                // Check for duplicate leave type excluding the current one
                $stmt_check = $conn->prepare("SELECT COUNT(*) FROM leave_types WHERE leave_type = ? AND id != ?");
                $stmt_check->bind_param("si", $leave_type, $id);
                $stmt_check->execute();
                $stmt_check->bind_result($count);
                $stmt_check->fetch();
                $stmt_check->close();

                if ($count > 0) {
                    $message = "Error: A leave type with this name already exists.";
                } else {
                    $stmt = $conn->prepare("UPDATE leave_types SET leave_type = ?, description = ?, default_entitlement = ? WHERE id = ?");
                    $stmt->bind_param("ssii", $leave_type, $description, $default_entitlement, $id);

                    if ($stmt->execute()) {
                        $message = "Leave type updated successfully!";
                    } else {
                        $message = "Error updating leave type: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? '';
            $stmt = $conn->prepare("DELETE FROM leave_types WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $message = "Leave type deleted successfully!";
            } else {
                $message = "Error deleting leave type: " . $stmt->error;
            }
            $stmt->close();
            break;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Manage Leave Types</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="stylee.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="employee.css">
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
            <div class="bg-white rounded-lg shadow-xl p-8 max-w-4xl w-full mx-auto">
                <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Manage Leave Types</h2>

                <?php if (!empty($message)): ?>
                    <div class="mb-4 p-3 rounded-md <?php echo strpos($message, 'Error') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?> text-center font-medium">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div id="add-update-form-container" class="mb-8 p-6 border border-gray-200 rounded-lg">
                    <h3 id="form-title" class="text-2xl font-semibold text-gray-700 mb-4 text-center">Add New Leave Type</h3>
                    <form id="leave-type-form" action="type.php" method="post" class="space-y-4">
                        <input type="hidden" name="action" id="form-action" value="add">
                        <input type="hidden" name="id_to_update" id="leave_type_id_to_update" value="">

                        <div>
                            <label for="leave_type" class="block text-gray-700 text-sm font-semibold mb-2 text-left">Leave Type Name:</label>
                            <input type="text" id="leave_type" name="leave_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                        </div>

                        <div>
                            <label for="default_entitlement" class="block text-gray-700 text-sm font-semibold mb-2 text-left">Default Entitlement (Days):</label>
                            <input type="number" id="default_entitlement" name="default_entitlement" required min="0"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                        </div>
                        
                        <div>
                            <label for="description" class="block text-gray-700 text-sm font-semibold mb-2 text-left">Description:</label>
                            <textarea id="description" name="description" rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out"></textarea>
                        </div>
                        
                        <div class="flex space-x-2">
                            <button type="submit" id="submit-button" class="w-full py-3 px-6 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-75">
                                Add Leave Type
                            </button>
                            <button type="button" id="cancel-button" onclick="resetForm()" class="w-full py-3 px-6 bg-gray-400 hover:bg-gray-500 text-white font-semibold rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-opacity-75 hidden">
                                Cancel Update
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="p-6 border border-gray-200 rounded-lg">
                    <h3 class="text-2xl font-semibold text-gray-700 mb-4">Existing Leave Types</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700 uppercase text-sm leading-normal">
                                    <th class="py-3 px-6 text-left">S.N.</th>
                                    <th class="py-3 px-6 text-left">Leave Type</th>
                                    <th class="py-3 px-6 text-left">Entitlement (Days)</th>
                                    <th class="py-3 px-6 text-left">Description</th>
                                    <th class="py-3 px-6 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm font-light">
                                <?php
                                // Fetch and display leave types
                                $sql = "SELECT id, leave_type, description, default_entitlement FROM leave_types ORDER BY leave_type";
                                $result = $conn->query($sql);

                                if ($result->num_rows > 0) {
                                    $serial_number = 1;
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr class='border-b border-gray-200 hover:bg-gray-50'>";
                                        echo "<td class='py-3 px-6 text-left whitespace-nowrap'>" . $serial_number++ . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["leave_type"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["default_entitlement"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . nl2br(htmlspecialchars($row["description"])) . "</td>";
                                        echo "<td class='py-3 px-6 text-center'>";
                                        echo "<div class='flex items-center justify-center space-x-2'>";
                                        // Edit button
                                        echo "<button type='button' onclick='populateFormForEdit(" . $row["id"] . ", \"" . htmlspecialchars($row["leave_type"], ENT_QUOTES) . "\", \"" . htmlspecialchars($row["description"], ENT_QUOTES) . "\", " . $row["default_entitlement"] . ")' class='px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold rounded-md'>Edit</button>";
                                        // Delete form
                                        echo "<form action='type.php' method='post' onsubmit='return confirmDelete(\"" . htmlspecialchars($row["leave_type"]) . "\");'>";
                                        echo "<input type='hidden' name='action' value='delete'>";
                                        echo "<input type='hidden' name='id' value='" . $row["id"] . "'>";
                                        echo "<button type='submit' class='px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded-md'>Delete</button>";
                                        echo "</form>";
                                        echo "</div>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='py-3 px-6 text-center text-gray-600'>No leave types found. Add a new one above!</td></tr>";
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
        // Function to populate the form for editing
        function populateFormForEdit(id, leaveType, description, entitlement) {
            document.getElementById('form-title').innerText = 'Update Leave Type';
            document.getElementById('leave_type').value = leaveType;
            document.getElementById('description').value = description;
            document.getElementById('default_entitlement').value = entitlement;
            
            document.getElementById('leave_type_id_to_update').value = id;
            document.getElementById('form-action').value = 'update';
            document.getElementById('submit-button').innerText = 'Update Leave Type';
            document.getElementById('submit-button').classList.remove('bg-green-600', 'hover:bg-green-700');
            document.getElementById('submit-button').classList.add('bg-blue-600', 'hover:bg-blue-700');
            document.getElementById('cancel-button').classList.remove('hidden');

            document.getElementById('add-update-form-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Function to reset the form to "Add New" mode
        function resetForm() {
            document.getElementById('form-title').innerText = 'Add New Leave Type';
            document.getElementById('leave-type-form').reset();
            document.getElementById('leave_type_id_to_update').value = '';
            document.getElementById('form-action').value = 'add';
            document.getElementById('submit-button').innerText = 'Add Leave Type';
            document.getElementById('submit-button').classList.remove('bg-blue-600', 'hover:bg-blue-700');
            document.getElementById('submit-button').classList.add('bg-green-600', 'hover:bg-green-700');
            document.getElementById('cancel-button').classList.add('hidden');
        }

        // Simple client-side script for confirmation
        function confirmDelete(leaveTypeName) {
            return confirm(`Are you sure you want to delete the leave type: ${leaveTypeName}? This action cannot be undone.`);
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>