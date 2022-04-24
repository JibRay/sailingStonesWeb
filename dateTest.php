<!DOCTYPE html>
<!-- Revised 12-Feb-2019 -->

<?php
?>

<html>
  <body>
  <?php
  echo(strtotime("now") . "<br>");
  echo(strtotime("-1 months") . "<br>");
  ?>
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
  </body>
</html>
