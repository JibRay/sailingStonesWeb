<?php
// weatherDb.php
// Revised 19-Apr-2016
// Functions used to get weather information from the mysql database.

define("DAILY",  3);
define("HOURLY", 4);

define("H_RAIN",  0);
define("H_WIND",  1);
define("H_TEMP",  2);
define("H_INSOL", 3);

define("D_BAT",     0);
define("D_MX_TEMP", 1);
define("D_WIND",    2);
define("D_MN_TEMP", 3);

define("P_RAIN",    0);
define("P_AV_WIND", 1);
define("P_MX_WIND", 2);
define("P_MN_WIND", 3);
define("P_AV_TEMP", 4);
define("P_MX_TEMP", 5);
define("P_MN_TEMP", 6);
define("P_INSOL",   7);
define("P_BAT",     8);

// Get the hourly values for $date, $hour from the database.
function getHourValues($dbConnection, $date, $hour) {
  $names = array("rain", "av_wind", "av_temperature", "av_insolation");
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
  $names = array("min_battery", "max_temperature", "max_wind",
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
  $values[P_MX_WIND] = $dayValues[D_WIND];    // Max wind.
  $values[P_MX_TEMP] = $dayValues[D_MX_TEMP]; // Max temperature.
  $values[P_MN_TEMP] = $dayValues[D_MN_TEMP]; // Min temperature.
  $values[P_BAT]     = $dayValues[D_BAT];     // Min battery.
  $totalInsolation = 0.0;
  $insolationCount = 0;
  for ($h = 0; $h < 24; $h++) {
    // Hour values are rain, av_temperature, av_wind, av_insolation.
    $hourValues = getHourValues($connection, $date, $h);
    $values[P_RAIN] += $hourValues[H_RAIN];          // Total rain.
    $values[P_AV_WIND] += $hourValues[H_WIND];       // Average wind.
    if ($hourValues[H_WIND] < $values[P_MN_WIND]) {
      $values[P_MN_WIND] = $hourValues[H_WIND];      // Min wind.
    }
    $values[P_AV_TEMP] += $hourValues[H_TEMP];       // Average temp.
    if ($hourValues[H_INSOL] > 6.0) {
      $values[P_INSOL] += $hourValues[H_INSOL];      // Average insolation.
      ++$insolationCount;
    }
  }
  // Compute average values.
  $values[P_AV_WIND] /= 24;
  $values[P_AV_TEMP] /= 24;
  if ($insolationCount > 0) {
    $values[P_INSOL] /= $insolationCount;
  } else {
    $values[P_INSOL] = 0.0;
  }
  return $values;
}
?>
