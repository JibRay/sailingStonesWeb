<!DOCTYPE html>
<!-- Revised 16-Oct-2018 -->

<?php
  function displayWaterHeader() {
    echo "<thead>\n";
    echo "<tr>\n";
    echo "<th>Event time</th>";
    echo "<th>Well pump</th>";
    echo "<th>Level (Gal)</th>";
    echo "</tr>\n";
    echo "</thead>\n";
  }

  function displayWaterTable($d) {
    $path = "data/" . $d . ".ws";
    if (file_exists($path)) {
      $dataFile = fopen($path, "r");
      echo "<table>\n";
      displayWaterHeader();
      echo "<tbody id=\"waterTable\" >\n";
      while(!feof($dataFile)) {
        $line = fgets($dataFile);
        // Skip blank and comment lines.
        if (strlen($line > 0) && substr($line, 0, 1) != "#") {
          echo "<tr>\n";
          $h = substr($line, 0, 2);
          $m = substr($line, 2, 2);
          $s = substr($line, 4, 2);
          echo "<td>" . $d . " " . $h . ":" . $m . ":" . $s . "</td>";
          if (substr($line, 6, 1) == "T") {
            echo "<td>On</td>";
          } else {
            echo "<td>Off</td>";
          }
          echo "<td>" . substr($line, 7, 4) . "</td>";
          echo "</tr>\n";
        }
      }
      echo "</tbody>\n";
      echo "</table>\n";
      fclose($dataPath);
    } else {
      echo "No data for " . $d;
    }
  }
?>

<html lang="en-US">
  <head>
    <title>Home Instrumentation</title>
  </head>
  <style type="text/css">
    body {
      background-color: #e8e8e8;
    }
    body, h1, h2, h3, p, table, th, td, input {
      font-family: "Arial", "Tahoma";
    }
    table, th, td {
      border: 1px solid black;
      border-collapse: collapse;
      border-color: #707070;
      margin-top: 5px;
      margin-bottom: 5px;
    }
    tbody {
      overflow: scroll;
    }
    th {
      border: 2px solid black;
      border-color: #707070;
    }
    th, td {
      padding: 5px;
    }
    input, button {
      font-size: 14px;
    }
  </style>
  <body>
    <h1 style="color:#004f08;">Home Instrumentation</h1>
    <h3>Water Storage State</h3>
    <p id="debug"></p>
    <form>
      <?php
        if (array_key_exists("begin_date", $_GET)) {
          $beginDate = $_GET["begin_date"];
        } else {
          // Set begin date to yesterday.
          $t = strtotime("-1 days");
          // $beginDate = date("Y-m-d G:i:s", $t);
          $beginDate = date("Y-m-d", $t);
        }
        echo "Date:";
        echo "<input\n";
          echo "type=\"date\"\n";
          echo "name=\"begin_date\"\n";
          echo "value=\"" . $beginDate . "\"\n";
        echo ">\n";
        echo "<input\n";
          echo "type=\"submit\"\n";
          echo "font-size=\"14px\"\n";
          echo "value = \"Update\"\n";
        echo "><br>\n";
      ?>
    </form>
    <?php
      displayWaterTable($beginDate);
    ?>
    <canvas id="storagePlot" width="875" height="280" </canvas>
    <script>
      var plot = document.getElementById("storagePlot");
      var pc = plot.getContext("2d");
      var prevPoint = [-1, -1];
      var prevPumpState = false;

      function drawXlabels(c) {
        c.strokeStyle = "#000000";
        for (i = 0; i < 25; i++) {
          c.strokeText(i, 67 + i * 33, 240);
        }
        c.strokeText("Hour", 350, 260);
      }

      function drawPumpOn(c, x, pumpState) {
        if (prevPumpState) {
          c.fillStyle = "#ff8080";
          //document.getElementById("debug").innerHTML =
          //  prevPoint[0].toString() + " " + x.toString();
          c.fillRect(75 + prevPoint[0], 25, x - prevPoint[0], 200);
        }
      }
      
      function plotWaterLevel(c) {
        var r;
        c.strokeStyle = "#0000ff";
        var table = document.getElementById("waterTable");
        if (table != null) {
          var rows = table.getElementsByTagName('tr');
          var rowCount = rows.length;
          for (r = 0; r < rowCount; r++) {
            var cells = rows[r].cells;

            // Get the current row data.
            var t = cells[0].innerHTML;
            var seconds = parseInt(t.substring(11, 13)) * 3600;
            seconds += parseInt(t.substring(14, 16)) * 60;
            seconds += parseInt(t.substring(17, 19));
            var pumpState = cells[1].innerHTML == "On";
            var gallons = cells[2].innerHTML;
            
            // Calculate the plot coordinates.
            var x = parseInt(.009259 * seconds, 10);
            var y = parseInt(.2 * gallons, 10);

            // Plot pump state and gallons.
            if (prevPoint[0] != -1) {
              drawPumpOn(c, x, pumpState);
              c.moveTo(75 + prevPoint[0], 225 - prevPoint[1]);
              c.lineTo(75 + x, 225 - y);
              c.stroke();
            }
            prevPoint[0] = x;
            prevPoint[1] = y;
            prevPumpState = pumpState;
          }
        }
      }

      // Draw plot area.
      pc.fillStyle = "#ffffff";
      pc.fillRect(75, 25, 800, 200);
      // Draw pump-on key.
      pc.fillStyle = "#ff8080";
      pc.fillRect(75, 0, 30, 20);
      pc.font = "14px Arial";
      pc.strokeStyle = "#000000";
      pc.strokeText("= Well pump on", 108, 15);

      // drawPumpOn(pc);

      // Draw grid.
      // Horizontal lines and labels.
      for (i = 0; i < 11; i++) {
        pc.beginPath();
        pc.moveTo(75, 25 + (i * 20));
        pc.lineTo(875, 25 + (i * 20));
        pc.strokeStyle = "#d0d0d0";
        pc.stroke();
        n = (10 - i) * 100;
        pc.strokeStyle = "#000000";
        pc.strokeText(n.toString(), 40, 30 + (i * 20));
      }
      pc.strokeStyle = "#d0d0d0";

      // Vertical lines.
      for (i = 0; i < 25; i++) {
        pc.beginPath();
        pc.moveTo(75 + i * 33, 25);
        pc.lineTo(75 + i * 33, 225);
        pc.stroke();
      }

      // Label Y axis.
      pc.strokeStyle = "#000000";
      pc.strokeText("Gal", 5, 100);

      drawXlabels(pc);
      plotWaterLevel(pc);
    </script>
  </body>
</html>

