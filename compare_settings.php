<?php
function extract_settings_keys($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    preg_match_all("/\(\'([^\']+)\'/", $content, $matches);
    return array_unique($matches[1]);
}

$localKeys = extract_settings_keys('database/driyum_db (11).sql');
$prodKeys = extract_settings_keys('database/u167160735_newdry (1).sql');

$missingInProd = array_diff($localKeys, $prodKeys);
$extraInProd = array_diff($prodKeys, $localKeys);

echo "--- Missing Settings Keys in Production ---\n";
foreach ($missingInProd as $k) echo "- $k\n";

echo "\n--- Extra Settings Keys in Production ---\n";
foreach ($extraInProd as $k) echo "- $k\n";
