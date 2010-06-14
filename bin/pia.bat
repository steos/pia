@echo off
set BASEDIR=%~dp0
php -d include_path=%BASEDIR%..\src %BASEDIR%pia.php %*
