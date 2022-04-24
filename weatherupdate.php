<?php require "/var/interwoof/playaWeather"; ?>
<?php
  // Revised 24-Apr-2016

  // This script processes a HTML POST from Rock7. This POST contains the
  // weather data from the Racetrack Playa weather station. The weather
  // data are contained in a 100 bytes.
  //
  // Binary format:
  // byte value        units  scale offset
  // ----+------------+------+-----+------
  //   0  rain         mm     0.25   0
  //   1  temp         c      0.25  -18.0
  //   2  wind         m/s    0.125  0
  //   3  insolation   w/m2   6.0    0
  //  - above repeats 23 more times -
  //  96  min-battery  v      0.1    0
  //  97  max-wind     m/s    0.25   0
  //  98  max-temp     c      0.25  -5.0
  //  99  min-temp     c      0.25  -25.0

  // =========================================================================
  // Functions

  // Get the binary data by parsing the hex string in the posted data.
  function getBinaryData() {
    $binaryData = array();
    $dataHex = $_POST["data"];
    for ($i = 0; $i < strlen($dataHex); $i = $i+2) {
      $hexValue = hexdec(substr($dataHex, $i, 2));
      array_push($binaryData, $hexValue);
    }
    return $binaryData;
  }

  // Return True if data being posted is binary. False otherwise.
  function isBinaryData() {
    $binary = False;
    $data = getBinaryData();
    for ($i = 0; $i < count($data); $i++) {
      // If byte values is less then ' ' or greater then '9' the data
      // must be binary.
      if ($data[$i] < 32 || $data[$i] > 57)
        $binary = True;
    }
    return $binary;
  }

  // Send rain and temperature info to selected SMS destinations.
  function sendRainAlert($rain, $lowTemperature) {
    // vtext.com is Verizon's SMS portal. PHP mail must be setup for this
    // to work.
    $to = "7076010881@vtext.com, 8052189637@vtext.com";
    $subject = "";
    $message = "24 hour rain fall = " . number_format($rain, 2) . " mm\n"
      . "low temperature = " . number_format($lowTemperature, 2) . " c\n";
    $headers = "From: rtpweather@gmail.com" . "\r\n";
    mail($to, $subject, $message, $headers);
  }

  // Post the incoming data to the SQL database.
  function postBinaryMessage() {
    global $dbPassword;

    $hourlyRawOffsets = array(-1, 0, 0, -1);
    $hourlyOffsets = array(0, -18.0, 0, 0);
    $hourlyScales = array(0.25, 0.25, 0.125, 6.0);
    $dailyOffsets = array(0, 0, -5.0, -25.0);
    $dailyScales = array(0.1, 0.25, 0.25, 0.25);
    $hourlyNames = array('rain', 'av_temperature', 'av_wind', 'av_insolation');
    $dailyNames =
      array('min_battery', 'max_wind', 'max_temperature', 'min_temperature');
    $hourlyUnit_ids = array(3, 7, 8, 10);
    $dailyUnit_ids = array(9, 8, 7, 7);

    // Not sure if date string from Rock7 contains the century.
    if (strpos($_POST["transmit_time"], "-") < 4) {
      $transmitTime = "20" . $_POST["transmit_time"];
    } else {
      $transmitTime = $_POST["transmit_time"];
    }
    try {
      $connection = new PDO("mysql:host=localhost;dbname=playaweather",
                            "interwoof", $dbPassword);
      $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      // Check to see if this message has already been posted. If the
      // the message serial number, momsn, already exists in the messages
      // table skip posting this message.
      $sql = "SELECT momsn FROM messages WHERE momsn = "
             . "'" . $_POST["momsn"] . "'";
      $statement = $connection->prepare($sql);
      $statement->execute();
      $result = $statement->fetchAll();
      //echo $sql;
      //echo "\n";
      if (count($result) > 0) {
        return;
      }

      // Insert message parameters.
      $sql = "INSERT INTO messages(transmit_time, iridium_latitude,"
             . " iridium_longitude, iridium_cep, imei, momsn) VALUES("
             . "'" . $transmitTime . "',"
             . "'" . $_POST["iridium_latitude"] . "',"
             . "'" . $_POST["iridium_longitude"] . "',"
             . "'" . $_POST["iridium_cep"] . "',"
             . "'" . $_POST["imei"] . "',"
             . "'" . $_POST["momsn"] . "')";
      //echo $sql;
      //echo "\n";
      $connection->exec($sql);
      $message_id = $connection->lastInsertId();

      // Get just the date portion of $transmitTime.
      $i = strpos($transmitTime, ' ');
      $t = substr($transmitTime, 0, $i);
      $readingTime = new DateTime($t);
      // Subtract one day. Readings are from the previous day.
      $readingTime->sub(new DateInterval('P1D'));

      $binaryData = getBinaryData();
      // The last four (96-99) bytes are daily readings.
      $period_id = 4;
      for ($n = 0; $n < 4; $n++) {
        $timeText = $readingTime->format('Y-m-d 23:59:59');
        $value = $binaryData[$n + 96];
        $value *= $dailyScales[$n];
        $value += $dailyOffsets[$n];
        $sql = "INSERT INTO readings(time, value, name, message_id,"
               . " period_id, units_id) VALUES("
               . "'" . $timeText . "',"
               . "'" . $value . "',"
               . "'" . $dailyNames[$n] . "',"
               . "'" . $message_id . "',"
               . "'" . $period_id . "',"
               . "'" . $dailyUnit_ids[$n] . "')";
        //echo $sql;
        //echo "\n";
        $connection->exec($sql);
      }
      // The first 96 bytes contain the hourly readings.
      $period_id = 3;
      $totalRain = 0.0;
      $minTemperature = 1000.0;
      for ($hour = 0; $hour < 24; $hour++) {
        $timeText = $readingTime->format('Y-m-d H:0:0');
        for ($n = 0; $n < 4; $n++) {
          $value = $binaryData[4 * $hour + $n] + $hourlyRawOffsets[$n];
          $value *= $hourlyScales[$n];
          $value += $hourlyOffsets[$n];
          if ($hourlyNames[$n] == 'rain') {
            $totalRain += $value;
          }
          if ($hourlyNames[$n] == 'av_temperature') {
            if ($value < $minTemperature) {
              $minTemperature = $value;
            }
          }
          $sql = "INSERT INTO readings(time, value, name, message_id,"
                 . " period_id, units_id) VALUES("
                 . "'" . $timeText . "',"
                 . "'" . $value . "',"
                 . "'" . $hourlyNames[$n] . "',"
                 . "'" . $message_id . "',"
                 . "'" . $period_id . "',"
                 . "'" . $hourlyUnit_ids[$n] . "')";
          //echo $sql;
          //echo "\n";
          $connection->exec($sql);
        }
        $readingTime->add(new DateInterval('PT1H'));
      }
      if ($totalRain >= 10.0) {
        sendRainAlert($totalRain, $minTemperature);
      }
    } catch(PDOException $e) {
      echo $sql . "failed:" . $e->getMessage();
    }
  }

  // =========================================================================
  // Main program

  // Determine the format of the data.
  if (isBinaryData()) {
    postBinaryMessage();
  }

  echo "OK";
  echo "\n";
?>

