$appDir = 'C:\Users\diong\Herd\Project_Osfero\Osfero_web\app'
$files = Get-ChildItem -Path $appDir -Recurse -Filter '*.php'
$count = 0
foreach ($file in $files) {
    if ($file.Name -eq "remove_bom.ps1" -or $file.Name -eq "fix_filament_v3.ps1") { continue }
    $content = [System.IO.File]::ReadAllText($file.FullName)
    if ($content -like '*<?php*' -and -not ($content -match '(?m)^namespace\s+[^;]+;')) {
        Write-Host "Missing namespace: $($file.FullName)"
        $count++
    }
}
Write-Host "Total files missing namespace: $count"
