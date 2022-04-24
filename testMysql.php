<?php
  $serverName = "localhost";
  $username = "interwoof";
  $password = "46.howard";

  // Create connection.
  $connection = new mysqli($serverName, $userName, $password);
  // Check connection.
  if ($connection->connect_error) {
    die("Connection failed: " . mysqli_connect_error());
  } else {
    echo "Connection successful\n";
  }
?>

