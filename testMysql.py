#! /usr/bin/env python

import MySQLdb as mdb
import sys

try:
  connection = mdb.connect('localhost', 'interwoof', '46.howard', 'playaWeather')
  cursor = connection.cursor();
  cursor.execute("SELECT VERSION()")
  version = cursor.fetchone()
  print "Database version : %s " % version
except mdb.Error, e:
  print "Error %d: %s" % (e.args[0], e.Args[1])
  sys.exit(1)
finally:
  if connection:
    connection.close()

