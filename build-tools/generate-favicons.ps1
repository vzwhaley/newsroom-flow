<#
.SYNOPSIS
    Generates the NewsFlow website favicon set from a single source SVG.

.DESCRIPTION
    Renders newsroom-flow-web/public/favicon.svg to PNGs (180 / 96 / 32) using
    headless Chrome (falls back to Edge), then assembles a favicon.ico
    (PNG-in-ICO container) from the 32px render. Outputs into
    newsroom-flow-web/public/:
        apple-touch-icon.png   (180x180, iOS)
        favicon-96x96.png      (96x96)
        favicon.ico            (32x32, universal fallback)

    The source SVG is the logo's newspaper mark (white on a brand-blue tile).

.NOTES
    Re-run after editing newsroom-flow-web/public/favicon.svg.
#>
param(
    [string]$Public = (Join-Path $PSScriptRoot "..\newsroom-flow-web\public")
)

$ErrorActionPreference = 'Continue'

$svgPath = Join-Path $Public 'favicon.svg'
if (-not (Test-Path $svgPath)) { throw "Source SVG not found: $svgPath" }
$svg = Get-Content $svgPath -Raw

$chrome = "C:\Program Files\Google\Chrome\Application\chrome.exe"
if (-not (Test-Path $chrome)) { $chrome = "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" }
if (-not (Test-Path $chrome)) { throw "Neither Chrome nor Edge found for headless rendering." }
"Renderer: $chrome"

$tmp = Join-Path $env:TEMP "nf_fav"
New-Item -ItemType Directory -Force -Path $tmp | Out-Null

function Render([int]$size, [string]$out) {
    # Resize the SVG to the target pixel box, wrap in a margin-free page, screenshot.
    $sized = $svg -replace 'width="64" height="64"', "width=`"$size`" height=`"$size`""
    $html = "<!doctype html><html><head><style>html,body{margin:0;padding:0;background:transparent}</style></head><body>$sized</body></html>"
    $hf = Join-Path $tmp "f$size.html"
    Set-Content -Path $hf -Value $html -Encoding utf8
    $uri = ([System.Uri]$hf).AbsoluteUri
    & $chrome --headless=new --disable-gpu --no-sandbox --hide-scrollbars `
        --force-device-scale-factor=1 --default-background-color=00000000 `
        --screenshot="$out" --window-size="$size,$size" "$uri" 2>$null | Out-Null
}

Render 180 (Join-Path $Public 'apple-touch-icon.png')
Render 96  (Join-Path $Public 'favicon-96x96.png')
Render 32  (Join-Path $tmp 'favicon-32.png')
Start-Sleep -Milliseconds 300

# Assemble favicon.ico as a single 32x32 PNG-compressed icon entry.
$png = [System.IO.File]::ReadAllBytes((Join-Path $tmp 'favicon-32.png'))
$ms = New-Object System.IO.MemoryStream
$bw = New-Object System.IO.BinaryWriter($ms)
$bw.Write([UInt16]0); $bw.Write([UInt16]1); $bw.Write([UInt16]1)            # ICONDIR: reserved, type=icon, count=1
$bw.Write([Byte]32); $bw.Write([Byte]32); $bw.Write([Byte]0); $bw.Write([Byte]0)  # width, height, colors, reserved
$bw.Write([UInt16]1); $bw.Write([UInt16]32)                                  # planes, bitcount
$bw.Write([UInt32]$png.Length); $bw.Write([UInt32]22)                        # bytesInRes, offset (6 + 16)
$bw.Write($png); $bw.Flush()
[System.IO.File]::WriteAllBytes((Join-Path $Public 'favicon.ico'), $ms.ToArray())
$bw.Dispose()

Remove-Item -Recurse -Force $tmp -ErrorAction SilentlyContinue

foreach ($f in @('apple-touch-icon.png', 'favicon-96x96.png', 'favicon.ico')) {
    $p = Join-Path $Public $f
    if (Test-Path $p) { "OK  $f  $((Get-Item $p).Length) bytes" } else { "MISSING $f" }
}
