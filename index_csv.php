<!DOCTYPE html>
<!-- Revised 06-Feb-2016 -->

<?php
function getWeatherTable() {
  $dataFile = fopen("playaWeather.csv", "r") or die("File open failed");

  $table = array();
  while (($line = fgets($dataFile, 4000)) !== false) {
    $row = explode(",", $line);
    $table[] = $row;
  }

  fclose($dataFile);
  return $table;
}

function displayWeatherTable($beginDate, $endDate) {
  $table = getWeatherTable();
  $beginTime = strtotime($beginDate . " 0:0:0");
  $endTime = strtotime($endDate . " 0:0:0");
  echo "<table>\n";
  echo "<thead>\n";
  echo "<tr>\n";
  foreach ($table[0] as $headerItem) {
    echo "<th>", $headerItem, "</th>\n";
  }
  echo "</tr>\n";
  echo "</thead>\n";
  echo "<tbody>\n";
  $rowBackground = true;
  for ($i = count($table) - 1; $i > 0; $i--) {
    $rowTime = strtotime($table[$i][0] . "0:0:0");
    if ($rowTime >= $beginTime && $rowTime <= $endTime) {
      if ($rowBackground) {
        echo "<tr style=\"background-color:Snow\">\n";
      } else {
        echo "<tr>\n";
      }
      $rowBackground = !$rowBackground;
      foreach ($table[$i] as $item) {
        echo "<td>", $item, "</td>\n";
      }
      echo "</tr>\n";
    }
  }
  echo "</tbody>\n";
  echo "</table>\n";
}
?>

<html lang="en-US">
  <head>
    <title>Interwoof North</title>
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
      width:100%;
      background-color: #f8f8f8;
    }
    table, th, td {
      border: 1px solid black;
      border-collapse: collapse;
      font-family: "Arial", "Tahoma";
    }
    tbody {
      height: 200px;
      overflow: scroll;
    }
    th {
      border: 2px solid black;
    }
    th, td {
      padding: 10px;
    }
  </style>
  <body>
    <img src="InterwoofNorthBackground.png" alt="North coast photo"
      style="max-width:100%; height:auto;">
    <h1 style="color:#004f08;">Interwoof North</h1>
    <p>This is the Interwoof North Campus. Main site is at
    <a href="http://www.interwoof.com"> interwoof.com</a><p>
    <hr>
    <h3>Race Track Playa Weather</h3>
    <form>
      <?php
        if ($_GET["begin_date"] == "") {
          // Set begin date to 15 days earlier then today.
          $t = strtotime("-15 days");
          $beginDate = date("m/d/Y", $t);
          $endDate = date("m/d/Y");
        } else {
          $beginDate = $_GET["begin_date"];
          $endDate = $_GET["end_date"];
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
      displayWeatherTable($beginDate, $endDate);
    ?>
  </body>
</html>

