<?php
require_once 'config/database.php';

echo "Table: products\n";
print_r(fetch_all("DESCRIBE products"));

echo "\nTable: categories\n";
print_r(fetch_all("DESCRIBE categories"));
