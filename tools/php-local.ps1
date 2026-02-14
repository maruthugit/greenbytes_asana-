param(
  [Parameter(ValueFromRemainingArguments=$true)]
  [string[]]$Args
)


# Prefer the real binary path (Scoop shims can otherwise point to a shim folder)
$phpExe = (& php -r "echo PHP_BINARY;") 2>$null
$phpExe = ($phpExe | Out-String).Trim()

if ([string]::IsNullOrWhiteSpace($phpExe)) {
  $phpExe = (Get-Command php).Path
}

# Scoop php path example: C:\Users\<you>\scoop\apps\php82\current\php.exe
$phpDir = Split-Path -Parent $phpExe
$extDir = Join-Path $phpDir 'ext'

$extensions = @(
  'php_openssl.dll',
  'php_mbstring.dll',
  'php_curl.dll',
  'php_fileinfo.dll',
  'php_pdo_mysql.dll',
  'php_mysqli.dll'
)

$iniArgs = @(
  '-d', "extension_dir=$extDir"
)

foreach ($ext in $extensions) {
  $iniArgs += @('-d', "extension=$ext")
}

& $phpExe @iniArgs @Args
exit $LASTEXITCODE
