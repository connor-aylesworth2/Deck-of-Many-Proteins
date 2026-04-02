<?php //PDO BABYYYYYYY

date_default_timezone_set('Europe/London'); // set default timezone

/* set vars for sql credentials for pdo */
$hostname = '127.0.0.1';
$database = 's2837739';
$username = 's2837739';
$password = '<PASSWORD>'; // use your actual password
$charset = "utf8mb4";

$dsn = "mysql:host=$hostname;dbname=$database;charset=$charset";

$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false];

try {
        $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
        die("Failed to connect to db: " . $e->getMessage());
}

?>

