<?php
require_once 'config/database.php';

function dump_table($table) {
    echo "Table: $table\n";
    $res = fetch_all("DESCRIBE $table");
    foreach($res as $r) {
        echo "- " . $r['Field'] . "\n";
    }
    echo "\n";
}

dump_table('products');
dump_table('categories');


