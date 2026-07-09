$ErrorActionPreference = "Stop"

$siteRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$source = Join-Path $siteRoot "assets\topics-source.png"
$outDir = Join-Path $siteRoot "assets\topics"
New-Item -ItemType Directory -Path $outDir -Force | Out-Null

Add-Type -AssemblyName System.Drawing

function Save-Crop([System.Drawing.Bitmap]$sourceBitmap, [string]$name, [int]$x, [int]$y, [int]$w, [int]$h) {
  $rect = [System.Drawing.Rectangle]::new($x, $y, $w, $h)
  $crop = $sourceBitmap.Clone($rect, $sourceBitmap.PixelFormat)
  $target = Join-Path $outDir "$name.png"
  if (Test-Path $target) { Remove-Item -LiteralPath $target -Force }
  $crop.Save($target, [System.Drawing.Imaging.ImageFormat]::Png)
  $crop.Dispose()
}

$bitmap = [System.Drawing.Bitmap]::FromFile($source)
try {
  $crops = @(
    @{n="swami-vivekananda";x=20;y=116;w=135;h=148},
    @{n="apj-abdul-kalam";x=164;y=116;w=135;h=148},
    @{n="mahatma-gandhi";x=307;y=116;w=135;h=148},
    @{n="chanakya";x=451;y=116;w=152;h=148},
    @{n="aryabhata";x=611;y=116;w=151;h=148},
    @{n="rani-lakshmibai";x=770;y=116;w=148;h=148},
    @{n="chhatrapati-shivaji";x=925;y=116;w=148;h=148},
    @{n="subhas-chandra-bose";x=1085;y=116;w=137;h=148},
    @{n="sardar-patel";x=1230;y=116;w=140;h=148},
    @{n="kalpana-chawla";x=1379;y=116;w=140;h=148},

    @{n="steve-jobs";x=20;y=367;w=128;h=136},
    @{n="elon-musk";x=156;y=367;w=144;h=136},
    @{n="bill-gates";x=307;y=367;w=142;h=136},
    @{n="sundar-pichai";x=457;y=367;w=138;h=136},
    @{n="satya-nadella";x=603;y=367;w=149;h=136},
    @{n="jensen-huang";x=760;y=367;w=154;h=136},
    @{n="larry-page";x=923;y=367;w=140;h=136},
    @{n="sergey-brin";x=1071;y=367;w=149;h=136},
    @{n="larry-page-sergey-brin";x=923;y=367;w=297;h=136},
    @{n="narayana-murthy";x=1228;y=367;w=141;h=136},
    @{n="ratan-tata";x=1377;y=367;w=140;h=136},

    @{n="krishna";x=20;y=601;w=132;h=120},
    @{n="arjuna";x=160;y=601;w=135;h=120},
    @{n="bhishma";x=309;y=601;w=132;h=120},
    @{n="karna";x=452;y=601;w=142;h=120},
    @{n="draupadi";x=602;y=601;w=140;h=120},
    @{n="yudhishthira";x=750;y=601;w=140;h=120},
    @{n="bhima";x=899;y=601;w=140;h=120},
    @{n="nakula";x=1048;y=601;w=140;h=120},
    @{n="sahadeva";x=1197;y=601;w=140;h=120},
    @{n="duryodhana";x=1345;y=601;w=151;h=120},

    @{n="rama";x=20;y=811;w=106;h=145},
    @{n="sita";x=132;y=811;w=101;h=145},
    @{n="hanuman";x=240;y=811;w=101;h=145},
    @{n="lakshmana";x=348;y=811;w=101;h=145},
    @{n="bharata";x=456;y=811;w=92;h=145},
    @{n="ravana";x=553;y=811;w=93;h=145},
    @{n="jatayu";x=653;y=811;w=101;h=145},
    @{n="vibhishana";x=765;y=811;w=77;h=145},

    @{n="honesty";x=854;y=812;w=76;h=146},
    @{n="courage";x=935;y=812;w=79;h=146},
    @{n="teamwork";x=1018;y=812;w=80;h=146},
    @{n="respect";x=1104;y=812;w=78;h=146},
    @{n="service";x=1188;y=812;w=74;h=146},
    @{n="perseverance";x=1268;y=812;w=79;h=146},
    @{n="responsibility";x=1351;y=812;w=81;h=146},
    @{n="compassion";x=1438;y=812;w=78;h=146}
  )

  foreach ($crop in $crops) {
    Save-Crop $bitmap $crop.n $crop.x $crop.y $crop.w $crop.h
  }

  Write-Host "Created $($crops.Count) topic image crops in $outDir"
}
finally {
  $bitmap.Dispose()
}
