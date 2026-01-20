$ErrorActionPreference = 'Stop'

$phpIni = Join-Path $PSScriptRoot '..\php.local.ini'
$phpunit = Join-Path $PSScriptRoot '..\vendor\bin\phpunit'

& php -c $phpIni $phpunit
