<!DOCTYPE html>
<!-- Revised 5-May-2016 -->

<?php require "/var/interwoof/playaWeather"; ?>
<?php require "weatherDb.php"; ?>
<?php

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
  echo "    <p>Av Insol (W/m2)</p>\n";
  echo "  </div>\n";
  echo "  <div class='Cell'>\n";
  echo "    <p>Min Bat (V)</p>\n";
  echo "  </div>\n";
  echo "</div>\n";
}

function displayDailyTable($beginDate, $endDate) {
  global $dbPassword;

  echo "<div class='Table'>\n";
  displayDailyHeader();
  try {
    $connection = new PDO("mysql:host=localhost;dbname=playaweather",
                          "interwoof", $dbPassword);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $beginTime = new DateTime($beginDate . " 0:0:0");
    $endTime = new DateTime($endDate . " 0:0:0");
    $dayCount = 1 + date_diff($beginTime, $endTime)->days;

    $background = true; // This gets toggled on alternate rows.
    // Display all the daily rows in the date range. Oldest date is at the
    // top.
    for ($d = 0; $d < $dayCount; $d++) {
      $rowDate = $endTime->format('Y-m-d');
      echo "<div class='Row'>\n";
      //if ($background) {
      //  echo "<div class='Row' style='background-color:Snow'>\n";
      //} else {
      //  echo "<div class='Row' style='background-color:#fdfdfd'>\n";
      //}
      // Display the row content. First column is date followed by the
      // reading parameters. Clicking the date cell displays the hourly
      // values for that date.
      echo "<div class='Cell'>\n";
      echo "  <form action='dailyreport.php' method='get'>\n";
      echo "    <input type='hidden' name='date' value='" . $rowDate . "'>\n";
      echo "    <input type='submit' value='" . $rowDate . "'>\n";
      echo "  </form>\n";
      echo "</div>\n";
      $dayValues = calculateDayValues($connection, $rowDate);
      foreach($dayValues as $value) {
        echo "<div class='Cell'>\n";
        echo "  <p align='right'>", number_format($value, 2), "</p>\n";
        echo "</div>\n";
      }
      echo "</div>\n"; // class=Row.
      $endTime->sub(new DateInterval('P1D'));
      $backround = !$background; // Toggle the row background.
    }
  } catch(PDOException $e) {
    echo " Database connection failed: " . $e->getMessage();
  }
  $connection = null; // close the database connection.

  echo "</div>\n"; // class=Table.
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
      border-color: #707070;
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
    <a href="http://www.interwoof.com"> interwoof.com</a></p>
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
    <?php
      echo "<a href='dailycsv.php?begin_date="
             . $beginDate . "&end_date=" . $endDate . "'\n"; 
      echo   "download='daily_table.csv'>\n";
      echo   "<img src='downloadcsv.png' alt='Download'\n";
      echo     "width='50' height='57'>\n";
      echo "</a>\n";
      displayDailyTable($beginDate, $endDate);
    ?>
  </body>
</html>

