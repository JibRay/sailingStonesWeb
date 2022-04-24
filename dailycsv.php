<?php require "/var/interwoof/playaWeather"; ?>
<?php require "weatherDb.php"; ?>
<?php
  function sendHeader() {
    echo "Date, Rain (mm), Av Wind (m/s), Max Wind (m/s), "
         . "Min Wind (m/s), Av Temp (c), Max Temp (c), Min Temp (c), "
         . "Av Insol (W/m2), Min Bat (V)";
    echo "\n";
  }

  $beginDate = $_GET["begin_date"];
  $endDate = $_GET["end_date"];
  sendHeader();
  try {
    $connection = new PDO("mysql:host=localhost;dbname=playaweather",
                          "interwoof", $dbPassword);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $beginTime = new DateTime($beginDate . " 0:0:0");
    $endTime = new DateTime($endDate . " 0:0:0");
    $dayCount = 1 + date_diff($beginTime, $endTime)->d;
    for ($d = 0; $d < $dayCount; $d++) {
      $rowDate = $beginTime->format('Y-m-d');
      echo $rowDate;
      $dayValues = calculateDayValues($connection, $rowDate);
      foreach($dayValues as $value) {
        echo ", " . number_format($value, 2, '.', '');
      }
      echo "\n";
      $beginTime->add(new DateInterval('P1D'));
    }
  } catch(PDOException $e) {
    echo " Database connection failed: " . $e->getMessage();
  }
  $connection = null; // close the database connection.

?>
