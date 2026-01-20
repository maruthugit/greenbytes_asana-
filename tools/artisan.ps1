param(
  [Parameter(ValueFromRemainingArguments = $true)]
  [string[]] $Args
)

$ErrorActionPreference = 'Stop'

$phpIni = Join-Path $PSScriptRoot '..\php.local.ini'
$artisan = Join-Path $PSScriptRoot '..\artisan'

& php -c $phpIni $artisan @Args
