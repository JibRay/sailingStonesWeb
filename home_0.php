<!DOCTYPE html>
<!-- Revised 10-Oct-2018 -->

<html lang="en-US">
  <head>
    <title>Home Instrumentation</title>
  </head>
  <style type="text/css">
    body {
      background-color: #e8e8e8;
      font-family: "Arial", "Tahoma";
    }
    h1, h2, h3, p {
      font-family: "Arial", "Tahoma";
    }
  </style>
  <body>
    <h1 style="color:#004f08;">Home Instrumentation</h1>
    <h3>Water Storage State</h3>
    <form>
      <?php
        if (array_key_exists("begin_date", $_GET)) {
          $beginDate = $_GET["begin_date"];
          $endDate = $_GET["end_date"];
        } else {
          // Set begin date to 30 days earlier then today.
          $t = strtotime("-30 days");
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
    <canvas id="storagePlot" width="875" height="380" </canvas>
    <script>
      var plot = document.getElementById("storagePlot");
      var pc = plot.getContext("2d");

      function drawXlabels(c, firstHour) {
        c.strokeStyle = "#000000";
        for (i = 0; i < 25; i++) {
          d = firstHour + i;
          c.strokeText(d.toString(), 67 + i * 33, 340);
        }
        c.strokeText("Hours since 2018-09-10 00:00:00", 350, 360);
      }

      function drawPumpOn(c) {
        c.fillStyle = "#ff8080";
        c.fillRect(265, 25, 16, 300);
      }

      // Draw plot area.
      pc.fillStyle = "#ffffff";
      pc.fillRect(75, 25, 800, 300);
      // Draw pump-on key.
      pc.fillStyle = "#ff8080";
      pc.fillRect(75, 0, 30, 20);
      pc.font = "14px Arial";
      pc.strokeStyle = "#000000";
      pc.strokeText("= Well pump on", 108, 15);

      drawPumpOn(pc);

      // Draw grid.
      // Horizontal lines and labels.
      for (i = 0; i < 16; i++) {
        pc.beginPath();
        pc.moveTo(75, 25 + (i * 20));
        pc.lineTo(875, 25 + (i * 20));
        pc.strokeStyle = "#d0d0d0";
        pc.stroke();
        n = (15 - i) * 200;
        pc.strokeStyle = "#000000";
        pc.strokeText(n.toString(), 40, 30 + (i * 20));
      }
      pc.strokeStyle = "#d0d0d0";

      // Vertical lines.
      for (i = 0; i < 25; i++) {
        pc.beginPath();
        pc.moveTo(75 + i * 33, 25);
        pc.lineTo(75 + i * 33, 325);
        pc.stroke();
      }

      // Label Y axis.
      pc.strokeStyle = "#000000";
      pc.strokeText("Gal", 5, 150);

      drawXlabels(pc, 0);
    </script>
  </body>
</html>

