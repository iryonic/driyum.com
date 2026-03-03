$path = 'c:\xampp\htdocs\driyum.com\database\u167160735_newdry.sql'
$content = Get-Content -Path $path -Raw
$content = $content -replace "utf8mb4_uca1400_ai_ci", "utf8mb4_unicode_ci"
$content = $content -replace "utf8mb4_0900_ai_ci", "utf8mb4_unicode_ci"
$content | Set-Content -Path $path -Encoding UTF8
