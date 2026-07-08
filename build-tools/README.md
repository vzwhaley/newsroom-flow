# build-tools

Standalone scripts used to **build / generate assets and validate** the NewsroomFlow
project. These are *not* part of the web, Android, or iOS app source — they're
the one-off tools that produced committed assets or checked generated files.
Each writes into the appropriate app directory (or just validates) and is safe
to re-run.

| Script | What it does | Re-run when |
|--------|--------------|-------------|
| [`generate-favicons.ps1`](generate-favicons.ps1) | Renders `newsflow-web/public/favicon.svg` to PNGs (180/96/32) via headless Chrome/Edge, then assembles `favicon.ico`. Produces `apple-touch-icon.png`, `favicon-96x96.png`, `favicon.ico`. | The favicon SVG changes |
| [`generate-og-image.ps1`](generate-og-image.ps1) | Renders the 1200×630 Open Graph / Twitter share card (`newsflow-web/public/img/og-default.png`) — newspaper mark + wordmark + tagline on the brand gradient. | The brand, wordmark, or tagline changes |
| [`generate-android-icon.ps1`](generate-android-icon.ps1) | Draws the brand-blue **"NF"** monogram launcher icon (1024×1024 PNG) with System.Drawing → `newsflow-android/.../AppIcon`-style source. | The app icon design changes |
| [`optimize-hero-image.ps1`](optimize-hero-image.ps1) | Resizes/compresses a source photo into the homepage hero background `newsflow-web/public/images/hero-newspaper.jpg`. | A new hero photo is supplied |
| [`validate-pbxproj.py`](validate-pbxproj.py) | Sanity-checks the hand-authored iOS `project.pbxproj`: balanced braces/parens, matched `Begin/End` section markers, and no referenced-but-undefined object IDs. | Any manual edit to the Xcode project |

## Requirements

- **PowerShell** (Windows) for the `.ps1` tools. The favicon tool needs Chrome
  or Edge installed; the image tools use `System.Drawing` (built in on Windows).
- **Python 3** for `validate-pbxproj.py` (standard library only).

## Usage

Run from anywhere — each script resolves paths relative to the repo:

```powershell
# Regenerate the favicon set after editing favicon.svg
powershell -ExecutionPolicy Bypass -File build-tools/generate-favicons.ps1

# Regenerate the Android launcher icon
powershell -ExecutionPolicy Bypass -File build-tools/generate-android-icon.ps1

# Optimize a new hero photo (pass the source path)
powershell -ExecutionPolicy Bypass -File build-tools/optimize-hero-image.ps1 -Source "C:\path\to\photo.jpg"
```

```bash
# Validate the iOS Xcode project after a manual pbxproj edit
python build-tools/validate-pbxproj.py
```
