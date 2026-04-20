$Utf8NoBomEncoding = New-Object System.Text.UTF8Encoding($false)
$appDir = 'C:\Users\diong\Herd\Project_Osfero\Osfero_web\app'
$files = Get-ChildItem -Path $appDir -Recurse -Filter '*.php'
$count = 0
foreach ($file in $files) {
    # Check if file has BOM or if we should just rewrite it anyway
    $content = [System.IO.File]::ReadAllText($file.FullName)
    [System.IO.File]::WriteAllText($file.FullName, $content, $Utf8NoBomEncoding)
    $count++
}
Write-Host "Rewrote $count files as UTF-8 without BOM."
