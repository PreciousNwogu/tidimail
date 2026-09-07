Add-Type -AssemblyName System.Drawing

function New-TidimailIcon([int]$size, [string]$path) {
  $bmp = New-Object System.Drawing.Bitmap $size, $size
  $g = [System.Drawing.Graphics]::FromImage($bmp)
  $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
  $g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAlias
  $g.Clear([System.Drawing.Color]::FromArgb(255, 47, 93, 80))
  $pad = [int]($size * 0.12)
  $inner = $size - (2 * $pad)
  $linen = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(255, 244, 239, 230))
  $g.FillEllipse($linen, $pad, $pad, $inner, $inner)
  $fontSize = [float]($size * 0.42)
  $font = New-Object System.Drawing.Font "Georgia", $fontSize, ([System.Drawing.FontStyle]::Bold), ([System.Drawing.GraphicsUnit]::Pixel)
  $sf = New-Object System.Drawing.StringFormat
  $sf.Alignment = [System.Drawing.StringAlignment]::Center
  $sf.LineAlignment = [System.Drawing.StringAlignment]::Center
  $sage = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(255, 47, 93, 80))
  $g.DrawString("T", $font, $sage, (New-Object System.Drawing.RectangleF 0, 0, $size, $size), $sf)
  $dir = Split-Path $path
  if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir | Out-Null }
  $bmp.Save($path, [System.Drawing.Imaging.ImageFormat]::Png)
  $g.Dispose()
  $bmp.Dispose()
  $font.Dispose()
  $linen.Dispose()
  $sage.Dispose()
}

$root = Split-Path -Parent $PSScriptRoot
if (-not $root) { $root = Get-Location }
$public = Join-Path (Get-Location) "public"
New-TidimailIcon 192 (Join-Path $public "icon-192.png")
New-TidimailIcon 512 (Join-Path $public "icon-512.png")
Write-Output "ok"
