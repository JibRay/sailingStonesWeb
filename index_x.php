<!DOCTYPE html>
<!-- Revised 12-Apr-2016 -->

<?php
// include '/var/interwoof/playaWeather';

function displayDailyHeader() {
  echo "<div class='Heading'>\n";
  echo "  <div class='Cell'>\n";
  echo "    <p>Date</p>\n";
  echo "  </div>\n";
  echo "  <div class='Cell'>\n";
  echo "    <p>Rain (mm)</p>\n";
  echo "  </div>\n";
  echo "  <div class='Cell'>\n";
  echo "    <p>Av Wind (m/sec)</p>\n";
  echo "  </div>\n";
  echo "  <div class='Cell'>\n";
  echo "    <p>Max Wind (m/sec)</p>\n";
  echo "  </div>\n";
  echo "  <div class='Cell'>\n";
  echo "    <p>Min Wind (m/sec)</p>\n";
  echo "  </div>\n";
  echo "  <div class='Cell'>\n";
  echo "    <p>Av Temp (c)</p>\n";
  echo "  </div>\n";
  echo "  <div class='Cell'>\n";
  echo "    <p>Max Temp (c)</p>\n";
  echo "  </div>\n";
  echo "  <div class='Cell'>\n";
  echo "    <p>Min Temp (c)</p>\n";
  echo "  </div>\n";
  echo "  <div class='Cell'>\n";
  echo "    <p>Av Insol (W/M2)</p>\n";
  echo "  </div>\n";
  echo "  <div class='Cell'>\n";
  echo "    <p>Min Bat (V)</p>\n";
  echo "  </div>\n";
  echo "</div>\n";
}

// Get the hourly values for $date, $hour from the database.
function getHourValues($dbConnection, $date, $hour) {
  $names = array("rain", "av_temperature", "av_wind", "av_insolation");
  $values = array();
  $dateTime = $date . " " . $hour . ":00:00";
  try {
    foreach ($names as $name) {
      $sql = "SELECT value FROM readings WHERE time = '"
        . $dateTime . "' AND name = '" . $name . "'"
        . " AND period_id = 3";
      $statement = $dbConnection->prepare($sql);
      $statement->execute();
      $result = $statement->fetch();
      if ($result) {
        $value = floatval($result[0]);
        array_push($values, $value);
      } else {
        array_push($values, 0.0);
      }
    }
  } catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
  }
  return $values;
}

// Get the daily value for $date from the database.
function getDayValues($dbConnection, $date) {
  $names = array("min_battery", "max_wind", "max_temperature",
                 "min_temperature");
  $values = array();
  foreach ($names as $name) {
    $statement = $dbConnection->prepare(
      "SELECT value FROM readings WHERE time = '"
      . $date . "' AND name = '" . $name . "'"
      . " AND period_id = 4");
    $statement->execute();
    $result = $statement->fetch();
    if ($result) {
      $value = floatval($result[0]);
      array_push($values, $value);
    } else {
      array_push($values, 0.0);
    }
  }
  return $values;
}

// Using hourly and daily values for $date from the database,
// calculate the values for the specified day.
function calculateDayValues($connection, $date) {
  $dayValues = getDayValues($connection, $date . " 23:59:59");
  $values = array(0.0, 0.0, 0.0, 1000.0, 0.0, 0.0, 0.0, 0.0, 0.0);
  $values[2] = $dayValues[1]; // Max wind.
  $values[5] = $dayValues[2]; // Max temperature.
  $values[6] = $dayValues[3]; // Min temperature.
  $values[8] = $dayValues[0]; // Min battery.
  $totalInsolation = 0.0;
  $insolationCount = 0;
  for ($h = 0; $h < 24; $h++) {
    // Hour values are rain, av_temperature, av_wind, av_insolation.
    $hourValues = getHourValues($connection, $date, $h);
    $values[0] += $hourValues[0];       // Total rain.
    $values[1] += $hourValues[2];       // Average wind.
    if ($hourValues[2] < $values[3]) {
      $values[3] = $hourValues[2];      // Min wind.
    }
    $values[4] += $hourValues[1];       // Average temp.
    if ($hourValues[3] > 6.0) {
      $values[7] += $hourValues[3];     // Average insolation.
      ++$insolationCount;
    }
  }
  // Compute average values.
  $values[1] /= 24;
  $values[4] /= 24;
  if ($insolationCount > 0) {
    $values[7] /= $insolationCount;
  } else {
    $values[7] = 0.0;
  }
  return $values;
}

function displayDailyTable($beginDate, $endDate) {
  global $dbPassword;

  echo "<div class='Table'>\n";
  //echo "<div class='Title'>\n";
  //echo "<p>Racetrack Playa Weather</p>\n";
  //echo "</div>\n";
  displayDailyHeader();
  try {
    $connection = new PDO("mysql:host=localhost;dbname=playaweather",
                          "interwoof", "46.howard");
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $beginTime = new DateTime($beginDate . " 0:0:0");
    $endTime = new DateTime($endDate . " 0:0:0");
    $dayCount = 1 + date_diff($beginTime, $endTime)->d;
    $background = true; // This gets toggled on alternate rows.
    // Display all the daily rows in the date range. Oldest date is at the
    // top.
    for ($d = 0; $d < $dayCount; $d++) {
      $rowDate = $endTime->format('Y-m-d');
      if ($background) {
        echo "<div class='Row' style='background-color:Snow'>\n";
      } else {
        echo "<div class='Row' style='background-color:#fdfdfd'>\n";
      }
      // Display the row content. First column is date followed by the
      // reading parameters.
      echo "<div class='Cell'>\n";
      echo   "<form action='dailyreport.php' method='get'>\n";
      echo     "<input type='hidden' name='date' value='" . $rowDate . "'>\n";
      echo     "<input type='submit' value='" . $rowDate . "'>\n";
      echo   "</form>\n";
      echo "</div>\n";
      $dayValues = calculateDayValues($connection, $rowDate);
      foreach($dayValues as $value) {
        echo "<div class='Cell'>\n";
          echo   "<p>", number_format($value, 2), "</p>\n";
        echo "</div>\n";
      }
      echo "</div>\n";
      $endTime->sub(new DateInterval('P1D'));
      $backround = !$background; // Toggle the row background.
    }
  } catch(PDOException $e) {
    echo " Database connection failed: " . $e->getMessage();
  }
  $connection = null; // close the database connection.

  echo "</div>\n";
}
?>

<html lang="en-US">
  <head>
    <title>Interwoof North</title>
  </head>
  <style type="text/css">
    body {
      background-color: #e8e8ff;
      font-family: "Arial", "Tahoma";
    }
    h1, h2, h3, p {
      font-family: "Arial", "Tahoma";
    }
    .Table {
      display: table;
      background-color:#fdfdfd;
    }
    .Title {
      display: table-caption;
      text-align: center;
      font-weight: bold;
      font-size: large;
    }
    .Heading {
      display: table-row;
      font-weight: bold;
      text-align: center;
    }
    .Row {
      display: table-row;
    }
    .Cell {
      font-size:80%;
      display: table-cell;
      border: solid;
      border-width: thin;
      padding-left 5px;
      padding-right: 5px;
    }
    input[type='date'] {
      font-size: 14px;
    }
    input[type='submit'] {
      font-size: 16px;
    }
  </style>
  <body>
    <img src="InterwoofNorthBackground.png" alt="North coast photo"
      style="max-width:100%; height:auto;">
    <h1 style="color:#004f08;">Interwoof North</h1>
    <p>This is the Interwoof North Campus. Main site is at
    <a href="http://www.interwoof.com"> interwoof.com</a><p>
    <hr>
    <h3>Racetrack Playa Weather (click on date for hourly readings)</h3>
    <form>
      <?php
        if (array_key_exists("begin_date", $_GET)) {
          $beginDate = $_GET["begin_date"];
          $endDate = $_GET["end_date"];
        } else {
          // Set begin date to 16 days earlier then today.
          $t = strtotime("-16 days");
          $beginDate = date("Y-m-d", $t);
          // Set end date to yesterday.
          $t = strtotime("-1 days");
          $endDate = date("Y-m-d", $t);
        }
        echo "Beginning date:";
        echo "<input\n";
          echo "type=\"date\"\n";
          echo "name=\"begin_date\"\n";
          echo "value=\"" . $beginDate . "\"\n";
        echo ">\n";
        echo "Ending date:\n";
        echo "<input\n";
          echo "type=\"date\"\n";
          echo "name=\"end_date\"\n";
          echo "value=\"" . $endDate . "\"\n";
        echo ">\n";
        echo "<input type=\"submit\" value = \"Update\"><br>\n";
      ?>
    </form>
    <form align="right">
      <?php
        displayDailyTable($beginDate, $endDate);
      ?>
    </form>
  </body>
</html>

