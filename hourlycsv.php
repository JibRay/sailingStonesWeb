<?php require "/var/interwoof/playaWeather"; ?>
<?php require "weatherDb.php"; ?>
<?php
  function sendHeader() {
    echo "Time, Rain (mm), Wind (m/s), Temp (c), Insol (W/m2)\n";
  }

  $date = $_GET["date"];
  sendHeader();
  try {
    $connection = new PDO("mysql:host=localhost;dbname=playaweather",
                          "interwoof", $dbPassword);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $rowBackground = true; // This gets toggled on alternate rows.
    for ($h = 0; $h < 24; $h++) {
      // Display the row content. First column is hour.`
      echo strval($h) . ":00:00";
      $values = getHourValues($connection, $date, $h);
      foreach($values as $value) {
        echo ", " . number_format($value, 2, '.', '');
      }
      echo "\n";
    }
  } catch(PDOException $e) {
    echo " Database connection failed: " . $e->getMessage();
  }
  $connection = null; // close the database connection.
?>

