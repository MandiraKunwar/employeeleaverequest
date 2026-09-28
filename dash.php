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

// SQL queries to get counts for each status
$total_sql = "SELECT COUNT(*) AS total FROM leave_applications WHERE employee_id = ?";
$approved_sql = "SELECT COUNT(*) AS approved FROM leave_applications WHERE employee_id = ? AND status = 'Approved'";
$pending_sql = "SELECT COUNT(*) AS pending FROM leave_applications WHERE employee_id = ? AND status = 'Pending'";
$rejected_sql = "SELECT COUNT(*) AS rejected FROM leave_applications WHERE employee_id = ? AND status = 'Rejected'";

// Function to get count using prepared statement
function getCount($conn, $sql, $employee_id) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row;
}

// Fetch the counts
$total = getCount($conn, $total_sql, $employee_id);
$approved = getCount($conn, $approved_sql, $employee_id);
$pending = getCount($conn, $pending_sql, $employee_id);
$rejected = getCount($conn, $rejected_sql, $employee_id);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Employee Dashboard</title>
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
    <input type="checkbox" id="checkbox">
    
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
            <div class="max-w-6xl mx-auto bg-white rounded-lg shadow-xl p-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Employee Dashboard</h1>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    
                    <div class="bg-gray-200 rounded-lg p-6 shadow-md flex items-center justify-between">
                        <div class="text-xl font-semibold text-gray-700">Total Applications</div>
                        <div class="text-4xl font-bold text-gray-800"><?php echo $total['total']; ?></div>
                    </div>

                    <div class="bg-green-100 rounded-lg p-6 shadow-md flex items-center justify-between">
                        <div class="text-xl font-semibold text-green-700">Approved Leaves</div>
                        <div class="text-4xl font-bold text-green-800"><?php echo $approved['approved']; ?></div>
                    </div>

                    <div class="bg-yellow-100 rounded-lg p-6 shadow-md flex items-center justify-between">
                        <div class="text-xl font-semibold text-yellow-700">Pending Leaves</div>
                        <div class="text-4xl font-bold text-yellow-800"><?php echo $pending['pending']; ?></div>
                    </div>

                    <div class="bg-red-100 rounded-lg p-6 shadow-md flex items-center justify-between">
                        <div class="text-xl font-semibold text-red-700">Rejected Leaves</div>
                        <div class="text-4xl font-bold text-red-800"><?php echo $rejected['rejected']; ?></div>
                    </div>
                </div>

                <div class="p-6 border border-gray-200 rounded-lg shadow-sm">
                    <h2 class="text-2xl font-semibold text-gray-700 mb-4 text-center">My Recent Leave Applications</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700 uppercase text-sm leading-normal">
                                    <th class="py-3 px-6 text-left">Leave Type</th>
                                    <th class="py-3 px-6 text-left">Start Date</th>
                                    <th class="py-3 px-6 text-left">End Date</th>
                                    <th class="py-3 px-6 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm font-light">
                                <?php
                                // Fetch and display the 5 most recent leave applications
                                $recent_sql = "SELECT 
                                            lt.leave_type, 
                                            la.start_date, 
                                            la.end_date, 
                                            la.status 
                                        FROM 
                                            leave_applications la
                                        JOIN 
                                            leave_types lt ON la.leave_type_id = lt.id
                                        WHERE 
                                            la.employee_id = ?
                                        ORDER BY 
                                            la.start_date DESC
                                        LIMIT 5";
                                
                                $stmt = $conn->prepare($recent_sql);
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
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='py-3 px-6 text-center text-gray-600'>No recent leave applications.</td></tr>";
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
</body>
</html>
<?php
$conn->close();
?>