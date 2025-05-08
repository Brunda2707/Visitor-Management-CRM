<?php
$host = 'visitorcrm-2025-rakeshpersonal84-9066.l.aivencloud.com';
$user = 'avnadmin';
$pass = 'AVNS_slbh5ukFE-MJA89ZalM';
$db   = 'defaultdb';

// Path to your CA certificate
$caCertPath = __DIR__ . '/ca.pem';  // Make sure ca.pem is in the same directory

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, $caCertPath, NULL, NULL);
mysqli_real_connect($conn, $host, $user, $pass, $db, 28174, NULL, MYSQLI_CLIENT_SSL);

if (mysqli_connect_errno()) {
    die('Database connection failed: ' . mysqli_connect_error());
}
// Set MySQL session timezone to IST
mysqli_query($conn, "SET time_zone = '+05:30'");
?>
