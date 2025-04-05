<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Render provides database connection info as environment variables
$host = getenv('MYSQL_HOST') ?: 'localhost';
$db = getenv('MYSQL_DATABASE') ?: 'my_database';
$user = getenv('MYSQL_USERNAME') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';
$port = getenv('MYSQL_PORT') ?: 3306;

// Create connection
$conn = new mysqli($host, $user, $password, $db, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP MySQL App</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        .status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PHP MySQL Application</h1>
        
        <div class="status success">
            <p>PHP Server is running correctly!</p>
            <p>PHP Version: <?php echo phpversion(); ?></p>
        </div>
        
        <div class="status <?php echo $conn->connect_error ? 'error' : 'success'; ?>">
            <p>Database connection: <?php echo $conn->connect_error ? 'Failed' : 'Successful'; ?></p>
            <?php if (!$conn->connect_error): ?>
                <p>Connected to MySQL database: <?php echo $db; ?></p>
            <?php else: ?>
                <p>Error: <?php echo $conn->connect_error; ?></p>
            <?php endif; ?>
        </div>
        
        <h2>Server Information</h2>
        <ul>
            <li>Server Software: <?php echo $_SERVER['SERVER_SOFTWARE']; ?></li>
            <li>Server Protocol: <?php echo $_SERVER['SERVER_PROTOCOL']; ?></li>
            <li>Request Method: <?php echo $_SERVER['REQUEST_METHOD']; ?></li>
            <li>Server Port: <?php echo $_SERVER['SERVER_PORT']; ?></li>
            <li>Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?></li>
        </ul>
        
        <h2>PHP Extensions</h2>
        <ul>
            <li>mysqli: <?php echo extension_loaded('mysqli') ? 'Loaded' : 'Not loaded'; ?></li>
            <li>PDO: <?php echo extension_loaded('pdo') ? 'Loaded' : 'Not loaded'; ?></li>
            <li>pdo_mysql: <?php echo extension_loaded('pdo_mysql') ? 'Loaded' : 'Not loaded'; ?></li>
            <li>zip: <?php echo extension_loaded('zip') ? 'Loaded' : 'Not loaded'; ?></li>
        </ul>
    </div>
</body>
</html>

<?php
// Close connection
$conn->close();
?>
<?php
header("Location: home.php");
exit;
?>
