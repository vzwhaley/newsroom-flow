<#
.SYNOPSIS
    Generates the default Open Graph / Twitter share image (1200x630 PNG).

.DESCRIPTION
    Renders a brand-gradient social card with the newspaper logo mark, the
    "NewsroomFlow" wordmark, and the tagline (the "by moon whale media, llc"
    signature uses the Spantaran brand font, embedded as a data: URI because
    file:// font subresources don't load reliably in headless Chrome), via
    headless Chrome (falls back to Edge). Output:
    newsroom-flow-web/public/img/og-default.png — referenced by SeoHead.vue as
    the default OG/Twitter image.

.NOTES
    Re-run if the brand, wordmark, or tagline changes.
#>
param(
    [string]$Out = (Join-Path $PSScriptRoot "..\newsroom-flow-web\public\img\og-default.png")
)

$ErrorActionPreference = 'Continue'

$chrome = "C:\Program Files\Google\Chrome\Application\chrome.exe"
if (-not (Test-Path $chrome)) { $chrome = "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" }
if (-not (Test-Path $chrome)) { throw "Neither Chrome nor Edge found for headless rendering." }
"Renderer: $chrome"

$icon = '<svg viewBox="0 0 24 24" width="124" height="124" fill="none" stroke="#ffffff" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z"/></svg>'

# Spantaran brand font for the "moon whale media, llc" signature — embed as a
# base64 data: URI (file:// font subresources don't load reliably headless).
$fontPath = Join-Path $PSScriptRoot "..\newsroom-flow-web\public\fonts\Spantaran.ttf"
$fontB64 = [Convert]::ToBase64String([System.IO.File]::ReadAllBytes($fontPath))

$html = @"
<!doctype html><html><head><meta charset="utf-8"><style>
  @font-face{font-family:'Spantaran';src:url(data:font/ttf;base64,$fontB64) format('truetype')}
  html,body{margin:0;padding:0}
  .card{position:relative;width:1200px;height:630px;box-sizing:border-box;
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    background:linear-gradient(135deg,#2563EB 0%,#1D4ED8 55%,#4F46E5 100%);color:#fff;
    font-family:Georgia,'Times New Roman',serif}
  .wm{font-size:92px;font-weight:700;letter-spacing:-1px;margin-top:14px;line-height:1}
  .wm .f{color:#bfdbfe}
  .tm{font-size:30px;vertical-align:super}
  .tag{font-size:38px;color:#e0e7ff;margin-top:14px;font-family:Arial,Helvetica,sans-serif}
  .by{position:absolute;bottom:34px;font-size:24px;color:#bfdbfe;font-family:Arial,Helvetica,sans-serif}
  .by .mw{font-family:'Spantaran',cursive;font-size:27px}
</style></head><body>
  <div class="card">
    $icon
    <div class="wm">Newsroom<span class="f">Flow</span><span class="tm">&#8482;</span></div>
    <div class="tag">Your own customized news topics, every day.</div>
    <div class="by">by <span class="mw">moon whale media, llc</span></div>
  </div>
</body></html>
"@

$tmp = Join-Path $env:TEMP "nf_og.html"
Set-Content -Path $tmp -Value $html -Encoding utf8
$uri = ([System.Uri]$tmp).AbsoluteUri

New-Item -ItemType Directory -Force -Path (Split-Path $Out) | Out-Null
& $chrome --headless=new --disable-gpu --no-sandbox --hide-scrollbars `
    --force-device-scale-factor=1 --screenshot="$Out" --window-size="1200,630" "$uri" 2>$null | Out-Null
Start-Sleep -Milliseconds 400
Remove-Item $tmp -ErrorAction SilentlyContinue

if (Test-Path $Out) { "WROTE: $Out  ($([Math]::Round((Get-Item $Out).Length / 1KB)) KB)" } else { "FAILED" }
