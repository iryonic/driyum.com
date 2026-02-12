<?php
$lines = file('checkout.php');
$out = "";
foreach ($lines as $i => $line) {
    if (strpos($line, 'foreach') !== false) {
        $out .= sprintf("%4d: %s\n", $i + 1, trim($line));
    }
}
file_put_contents('results_foreach.txt', $out);
?>
