<?php
// Start the session at the very beginning of the script.
session_start();

// Check if the employee is logged in.
if (!isset($_SESSION['employee_id'])) {
    header('Location: employeeprofile.php'); // Redirect to login if not logged in
    exit();
}

// Retrieve employee details from the session.
$employee_id = $_SESSION['employee_id'];
$employee_username = $_SESSION['employee_username'];

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
$employee_details = null; // Initialize employee details

// Function to check for duplicates (for username/email during update)
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

// Fetch current employee details for display
// Removed mobile_number from SELECT query
$stmt_fetch = $conn->prepare("SELECT e.full_name, e.gender, e.username, e.email, e.address, e.department_id, d.department_name
                                FROM employees e
                                LEFT JOIN departments d ON e.department_id = d.id
                                WHERE e.id = ?");
if ($stmt_fetch) {
    $stmt_fetch->bind_param("i", $employee_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    $employee_details = $result_fetch->fetch_assoc();
    $stmt_fetch->close();
} else {
    $message = "Error fetching employee details: " . $conn->error;
}


// Handle POST requests for profile update or password change
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $department_id = $_POST['department_id'] ?? null;
        $address = trim($_POST['address'] ?? '');

        // Server-side validation for profile update
        if (empty($full_name) || empty($gender) || empty($username) || empty($email) || empty($department_id)) {
            $message = "Error: Full Name, Gender, Username, Email, and Department are required.";
        } elseif (strlen($full_name) < 3 || strlen($full_name) > 100) {
            $message = "Error: Full Name must be between 3 and 100 characters.";
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $message = "Error: Username must be between 3 and 50 characters.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Error: Invalid email format.";
        } elseif (isDuplicate($conn, 'employees', 'username', $username, $employee_id)) {
            $message = "Error: Username already exists.";
        } elseif (isDuplicate($conn, 'employees', 'email', $email, $employee_id)) {
            $message = "Error: Email already exists.";
        } elseif (!empty($address) && strlen($address) > 255) { // Address is optional but has max length
            $message = "Error: Address cannot exceed 255 characters.";
        } else {
            $stmt_update = $conn->prepare("UPDATE employees SET full_name = ?, gender = ?, username = ?, email = ?, department_id = ?, address = ? WHERE id = ?");
            $stmt_update->bind_param("ssssisi", $full_name, $gender, $username, $email, $department_id, $address, $employee_id);

            if ($stmt_update->execute()) {
                $message = "Profile updated successfully!";
                // Update session username if it changed
                $_SESSION['employee_username'] = $username;
                // Re-fetch details to show updated info immediately
                $stmt_fetch_after_update = $conn->prepare("SELECT e.full_name, e.gender, e.username, e.email, e.address, e.department_id, d.department_name
                                                            FROM employees e
                                                            LEFT JOIN departments d ON e.department_id = d.id
                                                            WHERE e.id = ?");
                if ($stmt_fetch_after_update) {
                    $stmt_fetch_after_update->bind_param("i", $employee_id);
                    $stmt_fetch_after_update->execute();
                    $result_fetch_after_update = $stmt_fetch_after_update->get_result();
                    $employee_details = $result_fetch_after_update->fetch_assoc();
                    $stmt_fetch_after_update->close();
                }
            } else {
                $message = "Error updating profile: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Server-side validation for password change
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = "Error: All password fields are required.";
        } elseif (strlen($new_password) < 6) {
            $message = "Error: New password must be at least 6 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $message = "Error: New password and confirm password do not match.";
        } else {
            // Verify current password
            $stmt_verify_pass = $conn->prepare("SELECT password FROM employees WHERE id = ?");
            $stmt_verify_pass->bind_param("i", $employee_id);
            $stmt_verify_pass->execute();
            $result_verify_pass = $stmt_verify_pass->get_result();
            $user_pass = $result_verify_pass->fetch_assoc();
            $stmt_verify_pass->close();

            if ($user_pass && password_verify($current_password, $user_pass['password'])) {
                // Hash new password and update
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_update_pass = $conn->prepare("UPDATE employees SET password = ? WHERE id = ?");
                $stmt_update_pass->bind_param("si", $hashed_new_password, $employee_id);

                if ($stmt_update_pass->execute()) {
                    $message = "Password changed successfully!";
                } else {
                    $message = "Error changing password: " . $stmt_update_pass->error;
                }
                $stmt_update_pass->close();
            } else {
                $message = "Error: Current password is incorrect.";
            }
        }
    }
}

// Fetch departments for the dropdown
$departments = [];
$sql_departments = "SELECT id, department_name FROM departments ORDER BY department_name";
$result_departments = $conn->query($sql_departments);
if ($result_departments && $result_departments->num_rows > 0) {
    while ($row_dept = $result_departments->fetch_assoc()) {
        $departments[] = $row_dept;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Employee Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="stylee.css"> <!-- Your existing dashboard CSS -->
    <script src="https://cdn.tailwindcss.com"></script> <!-- Tailwind CSS for styling -->
     <link rel="stylesheet" href="stylee.css">
     <link rel="stylesheet" href="profile.css">
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
           

            <!-- Employee Navigation Bar -->
            <ul>
                <li>
                    <a href="dash.php">
                        <i class="fa fa-tachometer" aria-hidden="true"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="employeeprofile.php"> <!-- Link to itself -->
                        <i class="fa fa-user" aria-hidden="true"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li>
                    <a href="applyleave.php"> <!-- Placeholder for Apply Leave -->
                        <i class="fa fa-calendar-plus-o" aria-hidden="true"></i>
                        <span>Apply Leave</span>
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
            <!-- Employee Profile Management Content Starts Here -->
            <div class="bg-white rounded-lg shadow-xl p-8 w-full">
                <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Manage Your Profile</h2>

                <?php if (!empty($message)): ?>
                    <div class="mb-4 p-3 rounded-md <?php echo strpos($message, 'Error') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?> text-center font-medium">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($employee_details): ?>
                    <!-- Update Profile Details Form -->
                    <div class="form-section">
                        <h3>Update Personal Details</h3>
                        <form action="employeeprofile.php" method="post" class="space-y-4">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="form-grid">
                                <div>
                                    <label for="full_name" class="block text-gray-700 text-sm font-semibold mb-2">Full Name:</label>
                                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($employee_details['full_name']); ?>" required
                                           minlength="3" maxlength="100" title="Full Name must be between 3 and 100 characters."
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                                </div>
                                <div>
                                    <label for="gender" class="block text-gray-700 text-sm font-semibold mb-2">Gender:</label>
                                    <select id="gender" name="gender" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo ($employee_details['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ($employee_details['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo ($employee_details['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="username" class="block text-gray-700 text-sm font-semibold mb-2">Username:</label>
                                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($employee_details['username']); ?>" required
                                           minlength="3" maxlength="50" title="Username must be between 3 and 50 characters."
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                                </div>
                                <div>
                                    <label for="email" class="block text-gray-700 text-sm font-semibold mb-2">Email:</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($employee_details['email']); ?>" required
                                           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Please enter a valid email address (e.g., user@example.com)."
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                                </div>
                                <div>
                                    <label for="department_id" class="block text-gray-700 text-sm font-semibold mb-2">Department:</label>
                                    <select id="department_id" name="department_id" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo htmlspecialchars($dept['id']); ?>"
                                                    <?php echo ($employee_details['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['department_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <!-- Mobile Number field removed -->
                                <div class="full-width">
                                    <label for="address" class="block text-gray-700 text-sm font-semibold mb-2">Address:</label>
                                    <textarea id="address" name="address" rows="3"
                                              maxlength="255" title="Address cannot exceed 255 characters."
                                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out"><?php echo htmlspecialchars($employee_details['address']); ?></textarea>
                                </div>
                            </div>
                            <button type="submit" class="w-full py-3 px-6 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75">
                                Update Profile
                            </button>
                        </form>
                    </div>

                    <!-- Change Password Form -->
                    <div class="form-section mt-8">
                        <h3>Change Password</h3>
                        <form action="employeeprofile.php" method="post" class="space-y-4" onsubmit="return confirmPasswordChange();">
                            <input type="hidden" name="action" value="change_password">
                            <div>
                                <label for="current_password" class="block text-gray-700 text-sm font-semibold mb-2">Current Password:</label>
                                <input type="password" id="current_password" name="current_password" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                            </div>
                            <div class="password-input-container">
                                <label for="new_password" class="block text-gray-700 text-sm font-semibold mb-2">New Password:</label>
                                <input type="password" id="new_password" name="new_password" required minlength="6"
                                       title="New password must be at least 6 characters long."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                                <i class="fa fa-eye password-toggle" id="toggleNewPassword" onclick="togglePasswordVisibility('new_password', 'toggleNewPassword')"></i>
                            </div>
                            <div class="password-input-container">
                                <label for="confirm_password" class="block text-gray-700 text-sm font-semibold mb-2">Confirm New Password:</label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                                       title="Confirm password must match the new password and be at least 6 characters long."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 ease-in-out">
                                <i class="fa fa-eye password-toggle" id="toggleConfirmPassword" onclick="togglePasswordVisibility('confirm_password', 'toggleConfirmPassword')"></i>
                            </div>
                            <button type="submit" class="w-full py-3 px-6 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-75">
                                Change Password
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <p class="text-red-600 text-center">Unable to load your profile details. Please contact support.</p>
                <?php endif; ?>
            </div>
            <!-- Employee Profile Management Content Ends Here -->
        </section>
    </div>

    <script>
        function confirmPasswordChange() {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            if (newPass !== confirmPass) {
                alert("New password and confirm password do not match!");
                return false;
            }
            return confirm("Are you sure you want to change your password?");
        }

        // Function to toggle password visibility
        function togglePasswordVisibility(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
