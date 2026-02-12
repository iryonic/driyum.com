<?php
$lines = file('checkout.php');
foreach ($lines as $i => $line) {
    if (strpos($line, '$products_data[') !== false) {
        printf("%4d: %s\n", $i + 1, trim($line));
    }
}
?>
