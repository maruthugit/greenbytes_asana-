Param(
	[Parameter(Position = 0)]
	[string]$Script = 'dev'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$nodeDir = Join-Path $env:USERPROFILE 'scoop\apps\nodejs-lts\current'
$nodeExe = Join-Path $nodeDir 'node.exe'
$npmCmd = Join-Path $nodeDir 'npm.cmd'

if (-not (Test-Path $nodeExe) -or -not (Test-Path $npmCmd)) {
	throw "Node.js LTS not found at '$nodeDir'. Install it with: scoop install nodejs-lts"
}

$env:Path = "$nodeDir;$env:Path"

& $npmCmd run $Script
