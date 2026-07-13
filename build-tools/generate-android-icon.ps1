<#
.SYNOPSIS
    Generates the brand-blue "NF" monogram app icon (1024x1024 PNG).

.DESCRIPTION
    Draws a vertical brand gradient (#2563EB -> #1D4ED8) with a bold white "NF"
    wordmark centered, using System.Drawing. 1024x1024 is the master size
    consumed by the iOS AppIcon asset and as a source for Android mipmaps.

    Output path defaults to the iOS AppIcon slot; pass -Out to target elsewhere.

.NOTES
    Re-run if the icon design changes. Windows only (System.Drawing).
#>
param(
    [string]$Out = (Join-Path $PSScriptRoot "..\newsroom-flow-ios\NewsroomFlow\Assets.xcassets\AppIcon.appiconset\icon-1024.png")
)

Add-Type -AssemblyName System.Drawing

$size = 1024
$bmp = New-Object System.Drawing.Bitmap($size, $size)
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
$g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit

# Brand vertical gradient #2563EB -> #1D4ED8
$rect = New-Object System.Drawing.Rectangle(0, 0, $size, $size)
$c1 = [System.Drawing.Color]::FromArgb(255, 0x25, 0x63, 0xEB)
$c2 = [System.Drawing.Color]::FromArgb(255, 0x1D, 0x4E, 0xD8)
$brush = New-Object System.Drawing.Drawing2D.LinearGradientBrush($rect, $c1, $c2, 90)
$g.FillRectangle($brush, $rect)

# White "NF" monogram, bold
$fontFamily = New-Object System.Drawing.FontFamily("Segoe UI")
$font = New-Object System.Drawing.Font($fontFamily, 430, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
$white = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
$sf = New-Object System.Drawing.StringFormat
$sf.Alignment = [System.Drawing.StringAlignment]::Center
$sf.LineAlignment = [System.Drawing.StringAlignment]::Center
$g.DrawString("NF", $font, $white, (New-Object System.Drawing.RectangleF(0, -20, $size, $size)), $sf)

New-Item -ItemType Directory -Force -Path (Split-Path $Out) | Out-Null
$bmp.Save($Out, [System.Drawing.Imaging.ImageFormat]::Png)
$g.Dispose(); $bmp.Dispose()

"WROTE: $Out  ($((Get-Item $Out).Length) bytes)"
