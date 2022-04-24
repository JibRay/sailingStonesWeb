#! /usr/bin/env python
# Create a csv file from a date-range query of the playaWeather database.

import MySQLdb as mdb
import sys
import time
import datetime
from dateutil.parser import *
import argparse

#==============================================================================
#Globals

version = 2

#==============================================================================
# Functions

def parseCommandLine():
  parser = argparse.ArgumentParser(description="Create csv from query")
  parser.add_argument("csvFile", help="CSV output file")
  parser.add_argument("-b", "--beginDate",                                 \
                      help="Beginning date, default = 2016-04-01",         \
                      default="2016-04-01")
  parser.add_argument("-e", "--endDate",                                   \
                      help="Ending date, default = today", default="")
  parser.add_argument("-n", "--noHeader", help="No header in output csv",  \
                      action="store_true", default="False")
  return parser.parse_args()

def getRecordValue(record, valueName):
  value = ''
  for parameter in record:
    if (parameter['name'] == valueName):
      value = parameter['value']
      break
  return value

def getHourValues(dbCursor, date, hour):
  names = 'rain', 'av_temperature', 'av_wind', 'av_insolation'
  values = {}
  timeText = date.strftime('%Y-%m-%d') + ' ' + str(hour) + ':00:00'
  for name in names:
    sql = "SELECT value FROM readings WHERE time = '" + timeText + "' " \
          + "AND name = '" + name + "' AND period_id = 3"
    dbCursor.execute(sql)
    records = dbCursor.fetchall()
    if records:
      values[name] = float(records[0]['value'])
    else:
      values[name] = 0.0
  return values

def getDayValues(dbCursor, date):
  names = 'min_battery', 'max_wind', 'max_temperature', 'min_temperature'
  values = {}
  dateText = date.strftime('%Y-%m-%d') + " 23:59:59"
  for name in names:
    sql = "SELECT value FROM readings WHERE time = '" + dateText \
          + "' AND name = '" + name + "' AND period_id = 4"
    dbCursor.execute(sql)
    records = dbCursor.fetchall()
    if records:
      values[name] = float(records[0]['value'])
    else:
      values[name] = 0.0
  return values

# Using hourly and daily values for date from the database,
# calculate the values for the specified day.
def calculateDayValues(dbCursor, date):
  dayValues = getDayValues(dbCursor, date)
  values = {'rain': 0.0, 'av_wind': 0.0, 'max_wind': dayValues['max_wind'],   \
            'min_wind': 1000.0, 'av_temp': 0.0,                               \
            'max_temp': dayValues['max_temperature'],                         \
            'min_temp': dayValues['min_temperature'], 'av_insol': 0.0,        \
            'min_bat': dayValues['min_battery']}
  insolationCount = 0
  for h in range(24):
    hourValues = getHourValues(dbCursor, date, h)
    values['rain'] += hourValues['rain']
    values['av_wind'] += hourValues['av_wind']
    if hourValues['av_wind'] < values['min_wind']:
      values['min_wind'] = hourValues['av_wind']
    values['av_temp'] += hourValues['av_temperature']
    if hourValues['av_insolation'] > 6.0:
      values['av_insol'] += hourValues['av_insolation']
      insolationCount += 1
  # Compute average values.
  values['av_wind'] /= 24
  values['av_temp'] /= 24
  if insolationCount > 0:
    values['av_insol'] /= insolationCount
  else:
    values['av_insol'] = 0.0
  return values

#==============================================================================
# Main program

args = parseCommandLine()

includeHeader = args.noHeader == 'False'

# Connect to the database.
connection = mdb.connect('localhost', 'interwoof', '46.howard', 'playaWeather')
with connection:
  cursor = connection.cursor(mdb.cursors.DictCursor)

  # Compute the date/time period variables.
  beginTime = parse(args.beginDate)
  endTime = parse(args.endDate)
  beginTimeText = '{}-{:02}-{:02}'.format(beginTime.year, \
                  beginTime.month, beginTime.day)
  endTimeText = '{}-{:02}-{:02}'.format(endTime.year, \
                endTime.month, endTime.day)

  print 'Retrieve readings from', beginTimeText, 'to', endTimeText

  # Calculate the number of days in the period.
  timeDiff = endTime - beginTime
  dayCount = 1 + timeDiff.days

  # Open the output file.
  outputFile = open(args.csvFile, 'w')

  parameterNames = 'rain', 'av_wind', 'max_wind', 'min_wind', 'av_temp',  \
                   'max_temp', 'min_temp', 'av_insol', 'min_bat'

  # Write the header to the output file.
  if includeHeader:
    outputFile.write( \
      'Date, Rain (mm), Av Wind (m/s), Max Wind (m/s), Min Wind (m/s), ' \
      + 'Av Temp (c), Max Temp (c), Min Temp (c), Av Insol (W/M2), '     \
      + 'Min Bat (V)\n')

  for d in range(dayCount):
    recordTime = beginTime + datetime.timedelta(d)
    recordTimeText = '{}-{:02}-{:02}'.format(recordTime.year, \
                     recordTime.month, recordTime.day)
    values = calculateDayValues(cursor, recordTime)

    line = recordTimeText
    for p in parameterNames:
      line += ', {:0.2f}'.format(values[p])
    outputFile.write(line + '\n')

  outputFile.close()

