@echo off
setlocal
set DB_FILE=logger.db
set PROG_NAME=self-logger.bat
set USER_NAME=%USERNAME%


if not exist %DB_FILE% (
    sqlite3 %DB_FILE% "CREATE TABLE logs(id INTEGER PRIMARY KEY AUTOINCREMENT, user TEXT, date TEXT);"
)

sqlite3 %DB_FILE% "INSERT INTO logs(user, date) VALUES('%USER_NAME%', datetime('now','localtime'));"

for /f %%c in ('sqlite3 %DB_FILE% "SELECT COUNT(*) FROM logs;"') do set TOTAL=%%c
for /f "delims=" %%f in ('sqlite3 %DB_FILE% "SELECT date FROM logs ORDER BY id ASC LIMIT 1;"') do set FIRST=%%f

echo Programm name: %PROG_NAME%
echo Launched: %TOTAL% times
echo First launch: %FIRST%
echo ---------------------------------------------
echo User      ^| Date
echo ---------------------------------------------
sqlite3 %DB_FILE% "SELECT user || '        |     ' || date FROM logs ORDER BY id ASC;"
echo ---------------------------------------------
pause