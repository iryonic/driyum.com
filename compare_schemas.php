<?php
function extract_tables($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    $tables = [];
    preg_match_all('/CREATE TABLE `([^`]+)` \((.*?)\) ENGINE=/s', $content, $matches);
    for ($i = 0; $i < count($matches[1]); $i++) {
        $tableName = $matches[1][$i];
        $tableSchema = trim($matches[2][$i]);
        $lines = explode(",\n", $tableSchema);
        $columns = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '`')) {
                preg_match('/^`([^`]+)` (.*)$/', $line, $colMatch);
                if (isset($colMatch[1])) {
                    $columns[$colMatch[1]] = trim($colMatch[2]);
                }
            }
        }
        $tables[$tableName] = $columns;
    }
    return $tables;
}

$localFile = 'database/driyum_db (11).sql';
$prodFile = 'database/u167160735_newdry (1).sql';

$localTables = extract_tables($localFile);
$prodTables = extract_tables($prodFile);

$missingTables = array_diff(array_keys($localTables), array_keys($prodTables));

echo "=== Missing Tables ===\n";
foreach ($missingTables as $t) echo "- $t\n";

echo "\n=== Missing or Different Columns ===\n";
foreach ($localTables as $tableName => $localCols) {
    if (isset($prodTables[$tableName])) {
        $prodCols = $prodTables[$tableName];
        foreach ($localCols as $colName => $localDef) {
            if (!isset($prodCols[$colName])) {
                echo "Table: $tableName | COLUMN MISSING: $colName\n";
            } else {
                // Compare definitions (loosely)
                $prodDef = $prodCols[$colName];
                // Strip unnecessary stuff like backticks if any
                if ($localDef !== $prodDef) {
                    // Check if it's just a case difference or whitespace
                    if (strtolower(trim($localDef)) !== strtolower(trim($prodDef))) {
                        // Sometimes the dump has different defaults or nullability
                        echo "Table: $tableName | COLUMN DIFFERENT: $colName\n";
                        echo "  Local: $localDef\n";
                        echo "  Prod:  $prodDef\n";
                    }
                }
            }
        }
    }
}
