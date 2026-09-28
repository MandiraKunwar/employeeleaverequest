<?php
    $servername = "localhost";
    $dbname = "admin";
    $username_db = "root";
    $password_db = "";
    $error_message = ""; // Initialize an empty error message variable

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = $_POST['username'] ?? ''; // Use null coalescing to prevent undefined index error
        $password = $_POST['password'] ?? '';

        // Validation for required fields
        if (empty($username) || empty($password)) {
            $error_message = "Username and password are required.";
        } else {
            // Create connection
            $conn = new mysqli($servername, $username_db, $password_db, $dbname);

            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            // Using prepared statements to prevent SQL injection
            $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                setcookie('user', $username, time() + 3600, '/');
                header('Location: dashboard.php');
                exit(); // Important to stop script execution after a redirect
            } else {
                $error_message = 'Invalid username or password.';
            }

            $stmt->close();
            $conn->close();
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <form action="" method="post">
            <?php if (!empty($error_message)): ?>
                <p style="color: red;"><?php echo $error_message; ?></p>
            <?php endif; ?>
            
            <label>Name:</label><br>
            <input type="text" name="username" placeholder="Enter the Username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required><br>
            
            <label>Password:</label><br>
            <input type="password" name="password" placeholder="Enter the password" required><br>
            
            <input type="submit" value="Login"><br>
        </form>
    </div>
</body>
</html>