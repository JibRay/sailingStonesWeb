<?php
  // Revised 16-Mar-2016

  // This script processes a HTML POST from Rock7. This POST contains the
  // weather data from the Racetrack Playa weather station. The data are
  // in one of two possible formats. Each is sent once per day. The first
  // is the original format used by the first deployment and is a
  // coma-separated text string containing 5 values. The second is binary
  // and contains 24 hourly sets of 4 values plus one set of 4 daily values.
  //
  // Original daily report:
  //   rain (mm), max-wind (m/s), max-temp (c), min-temp (c), min-battery (V)
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

  // Post the incoming data to the SQL database.
  function postBinaryMessage() {
    $hourlyOffsets = array(0, -18.0, 0, 0);
    $hourlyScales = array(0.25, 0.25, 0.125, 6.0);
    $dailyOffsets = array(0, 0, -5.0, -25.0);
    $dailyScales = array(0.1, 0.25, 0.25, 0.25);
    $hourlyNames = array('rain', 'av_temperature', 'av_wind', 'av-insolation');
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
                            "interwoof", "46.howard");
      $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      // Insert message parameters.
      $sql = "INSERT INTO messages(transmit_time, iridium_latitude,"
             . " iridium_longitude, iridium_cep, imei) VALUES("
             . "'" . $transmitTime . "',"
             . "'" . $_POST["iridium_latitude"] . "',"
             . "'" . $_POST["iridium_longitude"] . "',"
             . "'" . $_POST["iridium_cep"] . "',"
             . "'" . $_POST["imei"] . "')";
      echo $sql;
      echo "\n";
      // $connection->exec($sql);
      $message_id = $connection->lastInsertId();

      // Get just the date portion of $transmitTime.
      $i = strpos($transmitTime, ' ');
      $t = substr($transmitTime, 0, $i);
      $readingTime = new DateTime($t);
      // Subtract one day. Readings are from the previous day.
      $readingTime->sub(new DateInterval('P1D'));

      $dataHex = $_POST["data"];
      $binaryData = array();
      for ($i = 0; $i < strlen($dataHex); $i = $i+2) {
        $hexValue = hexdec(substr($dataHex, $i, 2));
        array_push($binaryData, $hexValue);
        echo $hexValue;
        echo "\n";
      }
      // The last four (96-99) bytes are daily readings.
      $period_id = 4;
      for ($n = 0; $n < 4; $n++) {
        $timeText = $readingTime->format('Y-m-d 0:0:0');
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
        echo $sql;
        echo "\n";
        // $connection->exec($sql);
      }
      // The first 96 bytes contain the hourly readings.
      $period_id = 3;
      for ($hour = 0; $hour < 24; $hour++) {
        $timeText = $readingTime->format('Y-m-d H:0:0');
        for ($n = 0; $n < 4; $n++) {
          $value = $binaryData[4 * $hour + $n];
          $value *= $hourlyScales[$n];
          $value += $hourlyOffsets[$n];
          $sql = "INSERT INTO readings(time, value, name, message_id,"
                 . " period_id, units_id) VALUES("
                 . "'" . $timeText . "',"
                 . "'" . $value . "',"
                 . "'" . $hourlyNames[$n] . "',"
                 . "'" . $message_id . "',"
                 . "'" . $period_id . "',"
                 . "'" . $hourlyUnit_ids[$n] . "')";
          echo $sql;
          echo "\n";
          // $connection->exec($sql);
        }
        $readingTime->add(new DateInterval('PT1H'));
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
?>

