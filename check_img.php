<?php
$files = [
    'assets/images/hero/pinebannerdesk.jpeg',
    'assets/images/hero/pinebannermob.jpeg',
    'assets/images/hero/pinebannertab.jpeg'
];
foreach($files as $f) {
    if(file_exists($f)) {
        $size = getimagesize($f);
        echo "$f: {$size[0]}x{$size[1]} (Ratio: " . ($size[0]/$size[1]) . ")\n";
    } else {
        echo "$f: NOT FOUND\n";
    }
}
?>
