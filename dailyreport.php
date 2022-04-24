<!DOCTYPE html>
<!-- Revised 19-Apr-2016 -->

<!-- PHP functions -->
<?php require "/var/interwoof/playaWeather"; ?>
<?php require "weatherDb.php"; ?>
<?php
function displayHeader() {
  echo "<table>\n";
  echo "<thead>\n";
  echo "<tr>\n";
  echo "<th>Time</th>";
  echo "<th>Rain (mm)</th>";
  echo "<th>Wind (m/sec)</th>";
  echo "<th>Temp (c)</th>";
  echo "<th>Insol (W/M2)</th>";
  echo "</tr>\n";
  echo "</thead>\n";
}

function displayHourlyTable($date) {
  global $dbPassword;

  displayHeader();
  echo "<tbody>\n";
  try {
    $connection = new PDO("mysql:host=localhost;dbname=playaweather",
                          "interwoof", $dbPassword);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $rowBackground = true; // This gets toggled on alternate rows.
    for ($h = 0; $h < 24; $h++) {
      if ($rowBackground) {
        echo "<tr style=\"background-color:Snow\">\n";
      } else {
        echo "<tr>\n";
      }
      // Display the row content. First column is hour.`
      echo "<td>", strval($h) . ":00:00", "</td>\n";
      $values = getHourValues($connection, $date, $h);
      foreach($values as $value) {
        echo "<td>", number_format($value, 2), "</td>\n";
      }
      $rowBackground = !$rowBackground; // Toggle the row background.
      echo "</tr>\n";
    }
  } catch(PDOException $e) {
    echo " Database connection failed: " . $e->getMessage();
  }
  $connection = null; // close the database connection.

  echo "</tbody>\n";
  echo "</table>\n";
}
?>

<html lang="en-US">
  <head>
    <title>Interwoof North playa daily weather</title>
  </head>
  <style>
    body {
      background-color: #e8e8ff;
      font-family: "Arial", "Tahoma";
    }
    h1, h2, h3, p {
      font-family: "Arial", "Tahoma";
    }
    table {
      font-size:75%;
      background-color: #f8f8f8;
    }
    table, th, td {
      border: 1px solid black;
      border-collapse: collapse;
      border-color: #707070;
      font-family: "Arial", "Tahoma";
    }
    tbody {
      height: 200px;
      overflow: scroll;
    }
    th {
      border: 2px solid black;
      border-color: #707070;
    }
    th, td {
      padding: 8px;
    }
  </style>
  <body>
    <h1 style="color:#004f08;">Racetrack Playa Weather</h1>
    <hr>
    <?php
      if (array_key_exists("date", $_GET)) {
        $reportDate = $_GET["date"];
      } else {
        $t = strtotime("-1 days");
        $reportDate = date("Y-m-d", $t);
      }
      echo "<h3>For " . $reportDate . " PST" . ":</h3>\n";
      echo "<a href='hourlycsv.php?date=" . $reportDate . "'\n"; 
      echo   "download='hourly_table.csv'>\n";
      echo   "<img src='downloadcsv.png' alt='Download'\n";
      echo     "width='50' height='57'>\n";
      echo "</a>\n";
      echo "<form align='right'>\n";
      displayHourlyTable($reportDate);
      echo "</form>\n";
    ?>
</html>

