#! /usr/bin/env python
# Update the playaWeather database from the file playaWeather.csv.

import MySQLdb as mdb
import sys
import time
import datetime

version = 2

connection = mdb.connect('localhost', 'interwoof', '46.howard', 'playaWeather')
with connection:
  cursor = connection.cursor(mdb.cursors.DictCursor)

  # Load the units table.
  cursor.execute("SELECT * FROM units")
  units = cursor.fetchall()

  # Load the periods table.
  cursor.execute("SELECT * FROM periods")
  periods = cursor.fetchall()

  # Read in the CSV file.
  csvReadings = [] # This a list of tuples.
  csvFile = open('playaWeather.csv', 'r')
  first = True
  for line in csvFile:
    if first:
      first = False; # Skip the first line, it is the headers.
    else:
      parameter = line.split(',')
      readingTime = time.strptime(parameter[0], '%m/%d/%Y')
      csvLine = readingTime,         \
                float(parameter[1]), \
                float(parameter[2]), \
                float(parameter[3]), \
                float(parameter[4]), \
                float(parameter[5])
      csvReadings.append(csvLine)
  csvFile.close()

  # Insert readings that are in the CSV file but not already in the database.
  unitsTable = 0, 3, 8, 7, 7, 9
  nameTable = 'time', 'rain', 'wind', 'max_temperature', 'min_temperature', \
    'min_battery'
  insertions = 0
  for reading in csvReadings:
    timeText = '{}-{:02}-{:02} {:02}:{:02}:{:02}'.format(                 \
               reading[0].tm_year, reading[0].tm_mon, reading[0].tm_mday, \
               reading[0].tm_hour, reading[0].tm_min, reading[0].tm_sec)
    cursor.execute("SELECT time, period_id FROM readings")
    result = cursor.fetchall()
    found = False
    if result:
        for row in result:
          if (row['time'].year == reading[0].tm_year)     \
             and (row['time'].month == reading[0].tm_mon) \
             and (row['time'].day == reading[0].tm_mday)  \
             and (row['period_id'] == 4):
            found = True
    else:
      print "query empty"
      found = False
    if found == False:
      # Insert each of the parameters from the CSV reading line.
      for i in range(1, 6):
        sql = "INSERT INTO readings"                        \
              + "(time, period_id, name, value, units_id)"  \
              + " VALUES('" + timeText + "', 4,"            \
              + "'" + nameTable[i] + "',"                   \
              + "{},{})".format(reading[i], unitsTable[i])
        cursor.execute(sql)
        insertions += 1
print "added", insertions, "new records"

