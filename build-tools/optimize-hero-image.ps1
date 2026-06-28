<#
.SYNOPSIS
    Resizes / compresses a source photo into the homepage hero background.

.DESCRIPTION
    Takes a full-resolution photo, scales it down to a max width (default 2000px,
    preserving aspect ratio) and re-encodes as JPEG at a sensible quality, writing
    newsflow-web/public/images/hero-newspaper.jpg. This keeps the hero crisp on
    large displays while staying well under a few hundred KB.

.NOTES
    Windows only (System.Drawing). Re-run when a new hero photo is supplied.
#>
param(
    [Parameter(Mandatory = $true)]
    [string]$Source,

    [string]$Out = (Join-Path $PSScriptRoot "..\newsflow-web\public\images\hero-newspaper.jpg"),

    [int]$MaxWidth = 2000,
    [int]$Quality = 82
)

Add-Type -AssemblyName System.Drawing

if (-not (Test-Path $Source)) { throw "Source image not found: $Source" }

$src = [System.Drawing.Image]::FromFile($Source)
try {
    $scale = [Math]::Min(1.0, $MaxWidth / $src.Width)
    $w = [int]([Math]::Round($src.Width * $scale))
    $h = [int]([Math]::Round($src.Height * $scale))

    $dst = New-Object System.Drawing.Bitmap($w, $h)
    $g = [System.Drawing.Graphics]::FromImage($dst)
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $g.DrawImage($src, 0, 0, $w, $h)

    # JPEG encoder with explicit quality.
    $codec = [System.Drawing.Imaging.ImageCodecInfo]::GetImageEncoders() |
        Where-Object { $_.MimeType -eq 'image/jpeg' } | Select-Object -First 1
    $params = New-Object System.Drawing.Imaging.EncoderParameters(1)
    $params.Param[0] = New-Object System.Drawing.Imaging.EncoderParameter(
        [System.Drawing.Imaging.Encoder]::Quality, [long]$Quality)

    New-Item -ItemType Directory -Force -Path (Split-Path $Out) | Out-Null
    $dst.Save($Out, $codec, $params)

    $g.Dispose(); $dst.Dispose()
} finally {
    $src.Dispose()
}

"WROTE: $Out  (${w}x${h}, $([Math]::Round((Get-Item $Out).Length / 1KB)) KB)"
