<?php
// Start the session at the very beginning of the script.
// This is crucial for managing user sessions after successful login.
session_start();

// Database connection details.
// Ensure these match your actual database configuration.
$servername = "localhost";
$dbname = "admin"; // This must match the database name where your 'employees' table resides.
$username_db = "root";   // Your database username.
$password_db = "";       // Your database password.

// Check if the form has been submitted using the POST method.
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
    // Retrieve and sanitize user input.
    // trim() removes leading/trailing whitespace.
    $username_or_email = trim($_POST['username'] ?? '');
    $entered_password = $_POST['password'] ?? '';

    // Establish a new database connection.
    $conn = new mysqli($servername, $username_db, $password_db, $dbname);

    // Check if the database connection was successful.
    if($conn->connect_error){
        // Log the error internally for debugging, but show a generic message to the user.
        error_log("Database Connection Failed: " . $conn->connect_error);
        echo '<script>alert("Login failed due to a server error. Please try again later."); window.location.href = "'.htmlspecialchars($_SERVER['PHP_SELF']).'";</script>';
        exit(); // Terminate script execution.
    }

    // Prepare a SQL statement to prevent SQL injection.
    // We select the id, username, email, and the hashed password from the 'employees' table.
    // The query allows login using either the username or email.
    $stmt = $conn->prepare("SELECT id, username, email, password FROM employees WHERE username = ? OR email = ?");

    // Check if the statement preparation was successful.
    if ($stmt === false) {
        error_log("Prepare statement failed: " . $conn->error);
        echo '<script>alert("Login failed due to a server error. Please try again later."); window.location.href = "'.htmlspecialchars($_SERVER['PHP_SELF']).'";</script>';
        $conn->close(); // Close the database connection.
        exit(); // Terminate script execution.
    }

    // Bind parameters to the prepared statement. 'ss' indicates two string parameters.
    $stmt->bind_param("ss", $username_or_email, $username_or_email);

    // Execute the prepared statement.
    $stmt->execute();

    // Get the result set from the executed statement.
    $result = $stmt->get_result();

    // Check if exactly one user was found.
    if($result->num_rows === 1){
        // Fetch the user data as an associative array.
        $user = $result->fetch_assoc();
        $hashed_password_from_db = $user['password']; // Get the hashed password from the database.

        // Verify the entered plain-text password against the stored hashed password.
        // password_verify() is the correct and secure way to do this.
        if(password_verify($entered_password, $hashed_password_from_db)){
            // If passwords match, authentication is successful.
            // Store relevant user information in session variables.
            $_SESSION['employee_id'] = $user['id'];
            $_SESSION['employee_username'] = $user['username'];
            // You can add more session variables here, e.g., $_SESSION['employee_full_name'] = $user['full_name'];

            // Redirect the user to their dashboard page.
            // Ensure 'dashboard.php' is the correct path to your employee dashboard.
            header('Location: dash.php');
            exit(); // Terminate script execution after redirection.
        } else {
            // If password verification fails.
            echo '<script>alert("Invalid username/email or password."); window.location.href = "'.htmlspecialchars($_SERVER['PHP_SELF']).'";</script>';
        }
    } else {
        // If no user or multiple users are found (should ideally be only one).
        echo '<script>alert("Invalid username/email or password."); window.location.href = "'.htmlspecialchars($_SERVER['PHP_SELF']).'";</script>';
    }

    // Close the prepared statement and database connection.
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login</title>
    <!-- Link to your existing CSS file. Make sure 'admin.css' is in the same directory or adjust the path. -->
    <link rel="stylesheet" href="admin.css">
    <style>
        /* Basic styling for the login form to ensure it looks decent.
           These styles can be overridden or supplemented by 'admin.css'. */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-container h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .login-container label {
            display: block;
            text-align: left;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: calc(100% - 20px); /* Account for padding */
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Include padding in width */
        }
        .login-container input[type="submit"] {
            background-color:green;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        .login-container input[type="submit"]:hover {
            background-color: grey;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Employee Login</h2>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <label>Username :</label><br>
            <input type="text" name="username" placeholder="Enter Username " required><br>

            <label>Password:</label><br>
            <input type="password" name="password" placeholder="Enter the password" required><br>
            <input type="submit" value="Login"><br>
        </form>
    </div>
</body>
</html>
