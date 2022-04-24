<!DOCTYPE html>
<!-- Revised 04-Oct-2017 -->
<!--
The seismometer program modifies this file to display an event. See
the notes flagged with *!* below.
-->

<?php
// PHP functions.
?>

<html lang="en-US">
  <head>
    <title>Mattole Valley Seismograph</title>
  </head>
  <style type="text/css">
    body {
      background-color: #e8e8ff;
      font-family: "Arial", "Tahoma";
    }
    h1, h2, h3, p {
      font-family: "Arial", "Tahoma";
    }
    canvas {
      margin: 20px;
      padding:  20px;
      background: #ffffff;
      border: 1px solid #000000;
    }
  </style>
  <body>
    <h1 style="color:#004f08;">Mattole Valley Seismograph</h1>

    <!-- *!* The following tag is filled in by the seismometer program. -->
    <h3>Event start time = 03-Oct-2017 06:31:22.54Z</h3>

    <canvas id="seismoGraph" width="880" height="470">
    <script>
      // Functions ==========================================================
      function drawEventGraph(graph, dx, data, title, yLabels, xLabels) {
        const LEFT_MARGIN = 40;
        const RIGHT_MARGIN = 40;
        const TOP_MARGIN = 30;
        const BOTTOM_MARGIN = 20;
        const GRID_X = 100;
        const GRID_Y = 70;

        let context = graph.getContext("2d");
        let w = graph.width - LEFT_MARGIN - RIGHT_MARGIN;
        let h = graph.height - TOP_MARGIN - BOTTOM_MARGIN;

        // Draw the title.
        context.font = "20px Arial";
        context.fillText(title, 350, 22);
        // Draw the grid.
        context.font = "15px Arial";
        let i = 0;
        let steps = 1 + (w / GRID_X);
        for (i = 0; i < steps; i++) {  // Vertical lines.
          context.beginPath();
          context.moveTo(LEFT_MARGIN + (i * GRID_X), TOP_MARGIN);
          context.lineTo(LEFT_MARGIN + (i * GRID_X), TOP_MARGIN + h);
          context.strokeStyle = "#808080";
          context.stroke();
          context.fillText(xLabels[i], i * GRID_X, TOP_MARGIN + h + 20);
        }
        steps = 1 + (h / GRID_Y);
        for (i = 0; i < steps; i++) {  // Horizontal lines.
          context.beginPath();
          context.moveTo(LEFT_MARGIN, TOP_MARGIN + (i * GRID_Y));
          context.lineTo(graph.width - RIGHT_MARGIN, TOP_MARGIN + (i * GRID_Y));
          context.strokeStyle = "#808080";
          context.stroke();
            context.fillText(yLabels[i], 0, 5 + TOP_MARGIN + (i * GRID_Y));
        }

        // Draw the graph.
        let x = LEFT_MARGIN;
        context.beginPath();
        context.moveTo(x, data[0]);
        x += dx;
        for (i = 1; i < data.length; i++) {
          context.lineTo(x, data[i]);
          x += dx;
        }
        context.strokeStyle = "#0000FF";
        context.stroke();
      }

      // Main Code ==========================================================
      var graph = document.getElementById("seismoGraph");

      // *!* The following four variables are filled in by the seismometer
      // program. The plot area is fixed at 800 pixels wide by 420 high.
      // The size of the data array must be 800 / dx. The number of Y
      // labels is fixed at 7 and the number of X labels is fixed at 9.
      var dx = 50;
      var data = [240, 240, 240, 420, 50, 320, 150, 250, 200, 230, 240, 240,
                  240, 240, 240, 240, 240];
      var yLabels = [" 0.60", " 0.40", " 0.20", " 0.00", "-0.20",
                     "-0.40", "-0.60"];
      var xLabels = ["06:31:22.54", "06:31:23.54", "06:31:24.54",
                     "06:31:25.54", "06:31:26.54", "06:31:27.54", 
                     "06:31:28.54", "06:31:29.54", "06:31:30.54"]; 

      drawEventGraph(graph, dx, data, "Z axis acceleration - G",
                     yLabels, xLabels);
    </script>
  </body>
</html>
