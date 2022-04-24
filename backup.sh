#! /bin/sh
dumpFile=playaWeather-`date +%Y-%m-%d`.sql
mysqldump -u interwoof -p playaWeather >$dumpFile
# mv $dumpFile ~/Dropbox/Interwoof/sailingStones/databaseBackup/
scp $dumpFile pi@trembler:~
