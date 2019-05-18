@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../phpmetrics/phpmetrics/bin/phpmetrics
php "%BIN_TARGET%" %*
