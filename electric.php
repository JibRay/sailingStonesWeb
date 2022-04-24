<!DOCTYPE html>
<!-- Revised 20-Feb-2019 -->

<?php
  function displayUsageHeader() {
    echo "<thead>\n";
    echo "<tr>\n";
    echo "<th>Day</th>";
    echo "<th>House</th>";
    echo "<th>Cabin</th>";
    echo "<th>Generator</th>";
    echo "<th>PG&E</th>";
    echo "</tr>\n";
    echo "</thead>\n";
  }

  // Format of $d is YYYY-MM-DD where YYYY is the year and MM is the month
  // and DD is ignored. Function returns an array containing:
  // number-of-days, house-total, cabin-total.
  function displayUsageTable($d) {
    $path = "data/" . substr($d, 0, 7) . ".eu";
    $dayCount = 0;
    $houseTotal = 0;
    $cabinTotal = 0;
    if (file_exists($path)) {
      $dataFile = fopen($path, "r");
      echo "<table>\n";
      echo "Values are in kilowatt-hours";
      displayUsageHeader();
      echo "<tbody id=\"usageTable\" >\n";
      while(!feof($dataFile)) {
        $line = fgets($dataFile);
        // Skip blank and comment lines.
        if (strlen($line > 0) && substr($line, 0, 1) != "#") {
          $dayCount += 1;
          echo "<tr>\n";
          $day = substr($line, 0, 2);
          echo "<td>" . substr($d, 0, 7) . "-" . $day . "</td>";
          echo "<td>" . substr($line, 2, 3) . "</td>";
          echo "<td>" . substr($line, 5, 3) . "</td>";
          echo "<td>" . substr($line, 8, 3) . "</td>";
          echo "<td>" . substr($line, 11) . "</td>";
          echo "</tr>\n";
          $houseTotal += intval(substr($line, 2, 3));
          $cabinTotal += intval(substr($line, 5, 3));
        }
      }
      echo "</tbody>\n";
      echo "</table>\n";
      fclose($dataPath);
    } else {
      echo "No data for " . $d;
    }
    return array($dayCount, $houseTotal, $cabinTotal);
  }

  function displayBilling($d) {
    $path = "data/" . substr($d, 0, 7) . ".eu";
    $dayCount = 0;
    $houseTotal = 0.0;
    $cabinTotal = 0.0;
    if (file_exists($path)) {
      $dataFile = fopen($path, "r");
      while(!feof($dataFile)) {
        $line = fgets($dataFile);
        // Skip blank and comment lines.
        if (strlen($line > 0) && substr($line, 0, 1) != "#") {
          $dayCount += 1;
          $houseTotal += intval(substr($line, 2, 3));
          $cabinTotal += intval(substr($line, 5, 3));
        }
      }
      $houseBill = .24 * $houseTotal;
      $cabinBill = .24 * $cabinTotal;
      echo "<h4>Billing:</h4>";
      $text = sprintf("%d days, rate = $0.24", $dayCount);
      echo $text;
      echo "<br>";
      $text = sprintf("House: %d kWhs = $%0.2f", $houseTotal, $houseBill);
      echo $text;
      echo "<br>";
      $text = sprintf("Cabin: %d kWhs = $%0.2f", $cabinTotal, $cabinBill);
      echo $text;
      echo "<br>";
    }
  }
?>

<html lang="en-US">
  <head>
    <title>Home Electical Uage</title>
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
    <h3>Electrical Usage</h3>
    <p id="debug"></p>
    <div style="float:left; margin:5px">
      <form>
        <?php
          if (array_key_exists("month", $_GET)) {
            $month = $_GET["month"];
          } else {
            // Use previous month as default.
            $t = strtotime("-1 months");
            $month = date("Y-m-d", $t);
          }
          echo "Date:";
          echo "<input\n";
            echo "type=\"date\"\n";
            echo "name=\"month\"\n";
            echo "value=\"" . $month . "\"\n";
          echo ">\n";
          echo "<input\n";
            echo "type=\"submit\"\n";
            echo "font-size=\"14px\"\n";
            echo "value = \"Update\"\n";
          echo "><br>\n";
        ?>
      </form>
      <?php
        $v = displayUsageTable($month);
      ?>
    </div>
    <div style="float:left; margin:5px">
      <?php
        displayBilling($month);
      ?>
    </div>
    <div style="clear:both; margin:5px">
      <canvas id="storagePlot" width="800" height="280" </canvas>
      <script>
        // Javascript.
        var plot = document.getElementById("storagePlot");
        var pc = plot.getContext("2d");
        var prevPoint = [-1, -1];
        var prevPumpState = false;

        function drawXlabels(c, month) {
          c.strokeStyle = "#000000";
          for (i = 0; i < 31; i++) {
            c.strokeText(i + 1, 78 + i * 23, 240);
          }
          c.strokeText(month, 350, 260);
        }

        // Plot the data array. c is the plot context, color is a hex string
        // containing the RGB color. The data array contains up to 31 days
        // of power values. length is the number of days to plot.
        function plotKwh(data, length, c, offset, color) {
          // c.strokeStyle = color;
          // c.beginPath();
          c.fillStyle = color;
          c.moveTo(75, 225 - (data[0] * 2));
          for (i = 0; i < length; i++) {
            // c.lineTo(75 + (i * 23), 225 - (data[i] * 2));
            var x = 76 + (offset * 5) + (i * 23);
            var h = data[i] * 4;
            c.fillRect(x, 225 - h, 5, h);
          }
          // c.stroke();
        }

        // Returns the month number, which is 0 if there is no file.
        // c is the plot context.
        function plotUsage(c) {
          var month = 0;
          var table = document.getElementById("usageTable");
          if (table != null) {
            var rows = table.getElementsByTagName('tr');
            var rowCount = rows.length;
            var house = Array(31);
            var cabin = Array(31);
            var generator = Array(31);
            var pge = Array(31);
            for (r = 0; r < rowCount; r++) {
              var cells = rows[r].cells;
              var d = cells[0].innerHTML;
              var day = parseInt(d.substring(8), 10);
              month = parseInt(d.substring(5, 7), 10);
              house[day - 1] = parseInt(cells[1].innerHTML, 10);
              cabin[day - 1] = parseInt(cells[2].innerHTML, 10);
              generator[day - 1] = parseInt(cells[3].innerHTML, 10);
              pge[day - 1] = parseInt(cells[4].innerHTML, 10);
            }
            plotKwh(house, rowCount, c, 0, "#00bf00");
            plotKwh(cabin, rowCount, c, 1, "#0000bf");
            plotKwh(generator, rowCount, c, 2, "#bf0000");
            plotKwh(pge, rowCount, c, 3, "#404040");
          }
          return month;
        }

        // Draw plot area.
        pc.fillStyle = "#ffffff";
        pc.fillRect(75, 25, 715, 200);

        // Draw keys.
        pc.fillStyle = "#00bf00";
        pc.fillRect(75, 10, 30, 3);
        pc.font = "14px Arial";
        pc.strokeStyle = "#000000";
        pc.strokeText("House", 108, 15);

        pc.fillStyle = "#0000bf";
        pc.fillRect(175, 10, 30, 3);
        pc.strokeStyle = "#000000";
        pc.strokeText("Cabin", 208, 15);

        pc.fillStyle = "#bf0000";
        pc.fillRect(275, 10, 30, 3);
        pc.strokeStyle = "#000000";
        pc.strokeText("Generator", 308, 15);

        pc.fillStyle = "#404040";
        pc.fillRect(385, 10, 30, 3);
        pc.strokeStyle = "#000000";
        pc.strokeText("PG&E", 418, 15);

        // Draw grid.
        // Horizontal lines and labels.
        for (i = 0; i < 11; i++) {
          pc.beginPath();
          pc.moveTo(75, 25 + (i * 20));
          pc.lineTo(790, 25 + (i * 20));
          pc.strokeStyle = "#d0d0d0";
          pc.stroke();
          n = (10 - i) * 5;
          pc.strokeStyle = "#000000";
          pc.strokeText(n.toString(), 50, 30 + (i * 20));
        }
        pc.strokeStyle = "#d0d0d0";

        // Vertical lines.
        for (i = 0; i < 32; i++) {
          pc.beginPath();
          pc.moveTo(75 + i * 23, 25);
          pc.lineTo(75 + i * 23, 225);
          pc.stroke();
        }
        var months = ["January","February","March","April","May","June",
                 "July","August","September","October","November","December"];

        // Label Y axis.
        pc.strokeStyle = "#000000";
        pc.strokeText("kWh", 5, 100);

        var month = plotUsage(pc);
        if (month > 0) {
          drawXlabels(pc, months[month - 1]);
        }
      </script>
    </div>
  </body>
</html>

