Add-Type -AssemblyName System.Drawing

$root = Split-Path -Parent $PSScriptRoot
if (-not (Test-Path (Join-Path $root "public"))) { $root = Get-Location }
$srcPath = Join-Path $root "public/logo.jpeg"
$outPath = Join-Path $root "public/logo.png"

$src = [System.Drawing.Bitmap]::FromFile($srcPath)
$bmp = New-Object System.Drawing.Bitmap $src.Width, $src.Height, ([System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.DrawImage($src, 0, 0, $src.Width, $src.Height)
$g.Dispose()
$src.Dispose()

function Test-Bg([System.Drawing.Color]$c) {
  return ($c.R -ge 236 -and $c.G -ge 236 -and $c.B -ge 236)
}

$w = $bmp.Width
$h = $bmp.Height
$visited = New-Object "bool[,]" $w, $h
$queue = New-Object System.Collections.Generic.Queue[int]

function TryEnqueue([int]$x, [int]$y) {
  if ($x -lt 0 -or $y -lt 0 -or $x -ge $w -or $y -ge $h) { return }
  if ($visited[$x, $y]) { return }
  if (-not (Test-Bg $bmp.GetPixel($x, $y))) { return }
  $visited[$x, $y] = $true
  $queue.Enqueue(($y * $w) + $x)
}

for ($x = 0; $x -lt $w; $x++) { TryEnqueue $x 0; TryEnqueue $x ($h - 1) }
for ($y = 0; $y -lt $h; $y++) { TryEnqueue 0 $y; TryEnqueue ($w - 1) $y }

$clear = [System.Drawing.Color]::FromArgb(0, 255, 255, 255)
while ($queue.Count -gt 0) {
  $i = $queue.Dequeue()
  $x = $i % $w
  $y = [int][Math]::Floor($i / $w)
  $bmp.SetPixel($x, $y, $clear)
  TryEnqueue ($x - 1) $y
  TryEnqueue ($x + 1) $y
  TryEnqueue $x ($y - 1)
  TryEnqueue $x ($y + 1)
}

$minX = $w; $minY = $h; $maxX = 0; $maxY = 0
for ($y = 0; $y -lt $h; $y++) {
  for ($x = 0; $x -lt $w; $x++) {
    if ($bmp.GetPixel($x, $y).A -eq 0) { continue }
    if ($x -lt $minX) { $minX = $x }
    if ($y -lt $minY) { $minY = $y }
    if ($x -gt $maxX) { $maxX = $x }
    if ($y -gt $maxY) { $maxY = $y }
  }
}

$pad = [Math]::Max(8, [int](($maxX - $minX) * 0.04))
$minX = [Math]::Max(0, $minX - $pad)
$minY = [Math]::Max(0, $minY - $pad)
$maxX = [Math]::Min($w - 1, $maxX + $pad)
$maxY = [Math]::Min($h - 1, $maxY + $pad)
$rect = New-Object System.Drawing.Rectangle $minX, $minY, ($maxX - $minX + 1), ($maxY - $minY + 1)
$cropped = $bmp.Clone($rect, $bmp.PixelFormat)
$cropped.Save($outPath, [System.Drawing.Imaging.ImageFormat]::Png)
$cropped.Dispose()
$bmp.Dispose()
Write-Output "wrote $outPath"
