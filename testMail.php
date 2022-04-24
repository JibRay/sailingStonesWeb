<?php
  function sendRainAlert($rain, $lowTemperature) {
    $to = "7076010881@vtext.com, 8052189637@vtext.com";
    $subject = "";
    $message = "24 hour rain fall = " . number_format($rain, 2) . " mm\n"
      . "low temperature = " . number_format($lowTemperature, 2) . " c\n"
      . "(a test)\n";
    $headers = "From: rtpweather@gmail.com" . "\r\n";
    mail($to, $subject, $message, $headers);
  }

  $totalRain = 9.25;
  $minTemperature = -1.5;
  sendRainAlert($totalRain, $minTemperature);
  echo "OK";
  echo "\n";
?>

