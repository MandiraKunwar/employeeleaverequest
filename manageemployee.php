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

// Function to check for duplicates
function isDuplicate($conn, $table, $column, $value, $exclude_id = null) {
    $sql = "SELECT COUNT(*) FROM $table WHERE $column = ?";
    if ($exclude_id !== null) {
        $sql .= " AND id != ?";
    }
    $stmt = $conn->prepare($sql);
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
            $full_name = trim($_POST['full_name'] ?? '');
            $gender = trim($_POST['gender'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? ''; // Password should not be trimmed
            $department_id = $_POST['department_id'] ?? null;
            $address = trim($_POST['address'] ?? '');

            // Server-side validation
            if (empty($full_name) || empty($gender) || empty($username) || empty($email) || empty($password) || empty($department_id)) {
                $message = "All required fields must be filled.";
            } elseif (!preg_match("/^[a-zA-Z\s]+$/", $full_name)) {
                $message = "Full Name must contain only letters and spaces.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = "Invalid email format.";
            } elseif (isDuplicate($conn, 'employees', 'email', $email)) {
                $message = "Email already exists.";
            } elseif (!preg_match("/^[a-zA-Z][a-zA-Z0-9_]{4,14}$/", $username)) {
                $message = "Username must be 5-15 chars, start with a letter, and contain only letters, numbers, or underscores.";
            } elseif (preg_match("/__/", $username)) {
                $message = "Username cannot contain consecutive underscores.";
            } elseif (isDuplicate($conn, 'employees', 'username', $username)) {
                $message = "Username already exists.";
            } elseif (strlen($password) < 8) {
                $message = "Password must be at least 8 characters long.";
            } elseif (!preg_match("/[A-Z]/", $password)) {
                $message = "Password must contain at least one uppercase letter.";
            } elseif (!preg_match("/[a-z]/", $password)) {
                $message = "Password must contain at least one lowercase letter.";
            } elseif (!preg_match("/[0-9]/", $password)) {
                $message = "Password must contain at least one number.";
            } elseif (!preg_match("/[!@#$%^&*()\-_=+{};:,<.>]/", $password)) { // Added common special chars
                $message = "Password must contain at least one special character.";
            } else {
                // Hash the password before storing
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO employees (full_name, gender, username, email, password, department_id, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssis", $full_name, $gender, $username, $email, $hashed_password, $department_id, $address);

                if ($stmt->execute()) {
                    $message = "Employee added successfully!";
                } else {
                    $message = "Error adding employee: " . $stmt->error;
                }
                $stmt->close();
            }
            break;

        case 'update':
            $id = $_POST['id_to_update'] ?? '';
            $full_name = trim($_POST['full_name'] ?? '');
            $gender = trim($_POST['gender'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $department_id = $_POST['department_id'] ?? null;
            $address = trim($_POST['address'] ?? '');
            $password = $_POST['password'] ?? ''; // Only update if provided

            if (empty($id)) {
                $message = "Error: Employee ID for update is missing.";
                break;
            }
            // Server-side validation for update
            if (empty($full_name) || empty($gender) || empty($username) || empty($email) || empty($department_id)) {
                $message = "All required fields must be filled.";
            } elseif (!preg_match("/^[a-zA-Z\s]+$/", $full_name)) {
                $message = "Full Name must contain only letters and spaces.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = "Invalid email format.";
            } elseif (isDuplicate($conn, 'employees', 'email', $email, $id)) {
                $message = "Email already exists.";
            } elseif (!preg_match("/^[a-zA-Z][a-zA-Z0-9_]{4,14}$/", $username)) {
                $message = "Username must be 5-15 chars, start with a letter, and contain only letters, numbers, or underscores.";
            } elseif (preg_match("/__/", $username)) {
                $message = "Username cannot contain consecutive underscores.";
            } elseif (isDuplicate($conn, 'employees', 'username', $username, $id)) {
                $message = "Username already exists.";
            } elseif (!empty($password)) { // Only validate password if it's provided for update
                if (strlen($password) < 8) {
                    $message = "New password must be at least 8 characters long.";
                } elseif (!preg_match("/[A-Z]/", $password)) {
                    $message = "New password must contain at least one uppercase letter.";
                } elseif (!preg_match("/[a-z]/", $password)) {
                    $message = "New password must contain at least one lowercase letter.";
                } elseif (!preg_match("/[0-9]/", $password)) {
                    $message = "New password must contain at least one number.";
                } elseif (!preg_match("/[!@#$%^&*()\-_=+{};:,<.>]/", $password)) {
                    $message = "New password must contain at least one special character.";
                }
            }

            if (empty($message)) { // Proceed with update only if no validation errors so far
                $sql = "UPDATE employees SET full_name = ?, gender = ?, username = ?, email = ?, department_id = ?, address = ?";
                $types = "ssssis";
                $params = [$full_name, $gender, $username, $email, $department_id, $address];

                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql .= ", password = ?";
                    $types .= "s";
                    $params[] = $hashed_password;
                }
                $sql .= " WHERE id = ?";
                $types .= "i";
                $params[] = $id;

                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);

                if ($stmt->execute()) {
                    $message = "Employee updated successfully!";
                } else {
                    $message = "Error updating employee: " . $stmt->error;
                }
                $stmt->close();
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? '';

            $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $message = "Employee deleted successfully!";
            } else {
                $message = "Error deleting employee: " . $stmt->error;
            }
            $stmt->close();
            break;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Manage Employees</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="stylee.css"> <!-- Your existing dashboard CSS -->
    <script src="https://cdn.tailwindcss.com"></script> <!-- Tailwind CSS for employee management styles -->
    <link rel="stylesheet" href="employee.css">
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
                    <a href="manageemployee.php"> <!-- This link already points to itself -->
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
            <!-- Employee Management Content Starts Here -->
            <div class="bg-white rounded-lg shadow-xl p-8 max-w-4xl w-full">
                <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Manage Employees</h2>

                <?php if (!empty($message)): ?>
                    <div class="mb-4 p-3 rounded-md <?php echo strpos($message, 'Error') !== false || strpos($message, 'cannot be empty') !== false || strpos($message, 'already exists') !== false || strpos($message, 'Invalid') !== false || strpos($message, 'long') !== false || strpos($message, 'must contain') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?> text-center font-medium">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Add/Update Employee Form -->
                <div id="add-update-employee-form-container" class="mb-8 p-6 border border-gray-200 rounded-lg">
                    <h3 id="employee-form-title" class="text-2xl font-semibold text-gray-700 mb-4 text-center">Add New Employee</h3>
                    <form id="employee-form" action="manageemployee.php" method="post" class="space-y-4">
                        <input type="hidden" name="action" id="employee-form-action" value="add">
                        <input type="hidden" name="id_to_update" id="employee_id_to_update" value="">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="full_name" class="block text-gray-700 text-sm font-semibold mb-2 text-left">Full Name:</label>
                                <input type="text" id="full_name" name="full_name" placeholder="" required
                                       pattern="^[a-zA-Z\s]+$" title="Full Name must contain only letters and spaces."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                            </div>
                            <div>
                                <label for="gender" class="block text-gray-700 text-sm font-semibold mb-2 text-left">Gender:</label>
                                <select id="gender" name="gender" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label for="username" class="block text-gray-700 text-sm font-semibold mb-2 text-left">Username:</label>
                                <input type="text" id="username" name="username" placeholder="" required
                                       pattern="^[a-zA-Z][a-zA-Z0-9_]{4,14}$" title="5-15 chars, starts with letter, only letters/numbers/underscores, no consecutive underscores."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                            </div>
                            <div>
                                <label for="email" class="block text-gray-700 text-sm font-semibold mb-2 text-left">Email:</label>
                                <input type="email" id="email" name="email" placeholder="" required
                                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                       title="Please enter a valid email address (e.g., user@example.com)"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                            </div>
                            <div class="password-container">
                                <label for="password" class="block text-gray-700 text-sm font-semibold mb-2 text-left">Password :</label>
                                <input type="password" id="password" name="password" placeholder=""
                                       minlength="8"
                                       title="Password must be at least 8 characters long, include uppercase, lowercase, number, and special character."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                                <span class="password-toggle fa fa-eye" id="togglePassword"></span>
                                <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password during update.</p>
                            </div>
                            <div>
                                <label for="department_id" class="block text-gray-700 text-sm font-semibold mb-2 text-left">Department:</label>
                                <select id="department_id" name="department_id" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                                    <option value="">Select Department</option>
                                    <?php
                                    // Fetch departments for dropdown
                                    $sql_dept = "SELECT id, department_name FROM departments ORDER BY department_name";
                                    $result_dept = $conn->query($sql_dept);
                                    if ($result_dept->num_rows > 0) {
                                        while ($row_dept = $result_dept->fetch_assoc()) {
                                            echo "<option value='" . $row_dept["id"] . "'>" . htmlspecialchars($row_dept["department_name"]) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label for="address" class="block text-gray-700 text-sm font-semibold mb-2 text-left">Address:</label>
                                <textarea id="address" name="address" rows="3" placeholder=""
                                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out"></textarea>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" id="employee-submit-button" class="w-full py-3 px-6 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-75">
                                Add Employee
                            </button>
                            <button type="button" id="employee-cancel-button" onclick="resetEmployeeForm()" class="w-full py-3 px-6 bg-gray-400 hover:bg-gray-500 text-white font-semibold rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-opacity-75 hidden">
                                Cancel Update
                            </button>
                        </div>
                    </form>
                </div>

                <!-- List Existing Employees (for Update/Delete) -->
                <div class="p-6 border border-gray-200 rounded-lg">
                    <h3 class="text-2xl font-semibold text-gray-700 mb-4">Existing Employees</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700 uppercase text-sm leading-normal">
                                    <th class="py-3 px-6 text-left">S.N.</th>
                                    <th class="py-3 px-6 text-left">Full Name</th>
                                    <th class="py-3 px-6 text-left">Gender</th>
                                    <th class="py-3 px-6 text-left">Username</th>
                                    <th class="py-3 px-6 text-left">Email</th>
                                    <th class="py-3 px-6 text-left">Department</th>
                                    <th class="py-3 px-6 text-left">Address</th>
                                    <th class="py-3 px-6 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm font-light">
                                <?php
                                // Display existing employees
                                $sql = "SELECT e.id, e.full_name, e.gender, e.username, e.email, e.address, d.department_name, d.id as department_id
                                        FROM employees e
                                        LEFT JOIN departments d ON e.department_id = d.id
                                        ORDER BY e.full_name";
                                $result = $conn->query($sql);

                                if ($result->num_rows > 0) {
                                    $serial_number = 1;
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr class='border-b border-gray-200 hover:bg-gray-50'>";
                                        echo "<td class='py-3 px-6 text-left whitespace-nowrap'>" . $serial_number++ . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["full_name"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["gender"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["username"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["email"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["department_name"] ?? 'N/A') . "</td>";
                                        echo "<td class='py-3 px-6 text-left'>" . htmlspecialchars($row["address"]) . "</td>";
                                        echo "<td class='py-3 px-6 text-center'>";
                                        echo "<div class='flex items-center justify-center space-x-2'>";
                                        // Edit button to populate the form
                                        echo "<button type='button' onclick='populateEmployeeFormForEdit(" . $row["id"] . ", \"" . htmlspecialchars($row["full_name"], ENT_QUOTES) . "\", \"" . htmlspecialchars($row["gender"], ENT_QUOTES) . "\", \"" . htmlspecialchars($row["username"], ENT_QUOTES) . "\", \"" . htmlspecialchars($row["email"], ENT_QUOTES) . "\", \"" . htmlspecialchars($row["address"], ENT_QUOTES) . "\", " . ($row["department_id"] ?? 'null') . ")' class='px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold rounded-md'>Edit</button>";
                                        // Delete Form
                                        echo "<form action='manageemployee.php' method='post' onsubmit='return confirmDeleteEmployee(\"" . htmlspecialchars($row["full_name"]) . "\");'>";
                                        echo "<input type='hidden' name='action' value='delete'>";
                                        echo "<input type='hidden' name='id' value='" . $row["id"] . "'>";
                                        echo "<button type='submit' class='px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded-md'>Delete</button>";
                                        echo "</form>";
                                        echo "</div>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' class='py-3 px-6 text-center text-gray-600'>No employees found. Add a new one above!</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Employee Management Content Ends Here -->
        </section>
    </div>

    <script>
        // Function to populate the employee form for editing
        function populateEmployeeFormForEdit(id, fullName, gender, username, email, address, departmentId) {
            document.getElementById('employee-form-title').innerText = 'Update Employee';
            document.getElementById('full_name').value = fullName;
            document.getElementById('gender').value = gender;
            document.getElementById('username').value = username;
            document.getElementById('email').value = email;
            document.getElementById('address').value = address;
            document.getElementById('department_id').value = departmentId; // Set selected department

            // Clear password field for security (user can set new one)
            document.getElementById('password').value = '';
            document.getElementById('password').placeholder = 'Leave blank to keep current password';
            document.getElementById('password').removeAttribute('required'); // Password is optional on update

            document.getElementById('employee_id_to_update').value = id;
            document.getElementById('employee-form-action').value = 'update';
            document.getElementById('employee-submit-button').innerText = 'Update Employee';
            document.getElementById('employee-submit-button').classList.remove('bg-green-600', 'hover:bg-green-700');
            document.getElementById('employee-submit-button').classList.add('bg-blue-600', 'hover:bg-blue-700');
            document.getElementById('employee-cancel-button').classList.remove('hidden');

            // Scroll to the top of the form for better UX
            document.getElementById('employee-form-title').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Function to reset the employee form to "Add New Employee" mode
        function resetEmployeeForm() {
            document.getElementById('employee-form-title').innerText = 'Add New Employee';
            document.getElementById('employee-form').reset(); // Clears all form fields
            document.getElementById('employee_id_to_update').value = '';
            document.getElementById('employee-form-action').value = 'add';
            document.getElementById('employee-submit-button').innerText = 'Add Employee';
            document.getElementById('employee-submit-button').classList.remove('bg-blue-600', 'hover:bg-blue-700');
            document.getElementById('employee-submit-button').classList.add('bg-green-600', 'hover:bg-green-700');
            document.getElementById('employee-cancel-button').classList.add('hidden');

            // Restore password field to required for add mode
            document.getElementById('password').setAttribute('required', 'required');
            document.getElementById('password').placeholder = 'Set initial password';
        }

        // Simple client-side script for confirmation for delete
        function confirmDeleteEmployee(employeeName) {
            return confirm(`Are you sure you want to delete employee: ${employeeName}?`);
        }

        // Toggle password visibility
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');

            if (togglePassword && password) {
                togglePassword.addEventListener('click', function (e) {
                    // toggle the type attribute
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    // toggle the eye icon
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
