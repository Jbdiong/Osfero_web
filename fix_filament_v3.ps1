$Utf8NoBomEncoding = New-Object System.Text.UTF8Encoding($false)
$appDir = 'C:\Users\diong\Herd\Project_Osfero\Osfero_web\app'
$files = Get-ChildItem -Path $appDir -Recurse -Filter '*.php'
$count = 0
foreach ($file in $files) {
    try {
        $content = [System.IO.File]::ReadAllText($file.FullName)
    } catch {
        continue
    }
    $original = $content

    # Fix namespaces/imports
    $content = $content -replace 'Filament\\Schemas\\', 'Filament\Forms\'
    
    # Ensure Form import if used
    if ($content -like '*Form $form*' -and $content -notlike '*use Filament\Forms\Form;*') {
        # Insert after namespace or other imports
        if ($content -match 'namespace [^;]+;') {
            $content = $content -replace '(namespace [^;]+;)', "$1`r`n`r`nuse Filament\Forms\Form;"
        }
    }

    # Fix union types (v4 only)
    $content = $content -replace 'protected static string \| \\UnitEnum \| null ', 'protected static ?string '
    $content = $content -replace 'protected static string \| \\backedEnum \| null ', 'protected static ?string '

    # Fix method signatures (Schema -> Form)
    $content = $content -replace '\(Schema \$form\): Schema', '(Form $form): Form'
    $content = $content -replace '\(Schema \$form\)', '(Form $form)'

    # Fix $view property (static in v3)
    $content = $content -replace 'protected string \$view\s*=', 'protected static string $view ='

    if ($content -ne $original) {
        # De-duplicate any double imports we might have caused
        $lines = $content -split "`r`n"
        $newLines = @()
        $seenImports = @{}
        foreach ($line in $lines) {
            if ($line -match '^use [^;]+;') {
                if ($seenImports.ContainsKey($line)) {
                    continue
                }
                $seenImports[$line] = $true
            }
            $newLines += $line
        }
        $content = $newLines -join "`r`n"

        [System.IO.File]::WriteAllText($file.FullName, $content, $Utf8NoBomEncoding)
        Write-Host "Fixed: $($file.FullName)"
        $count++
    }
}
Write-Host "Total files fixed: $count"
