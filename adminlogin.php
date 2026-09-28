<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class = "login-container">
        <h2 > Admin Login</h2>
        <form action="adminSQL.php" method="post">
        
             <label>Name:</label><br>
            <input type="text" name="username" placeholder="Enter the Username" required><br>
            
             <label>Password:</label><br>
            <input type="password" name="password" placeholder="Enter the password" required ><br>
            <input type="submit" value="Login"><br>
        </form>
    </div>
</body>
</html>
