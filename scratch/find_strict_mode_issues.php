<?php
$dir = new RecursiveDirectoryIterator('c:/Users/star/Downloads/RestaurantProject-main');
$iterator = new RecursiveIteratorIterator($dir);
$matches = [];

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (strpos($content, "payment_time = ''") !== false || strpos($content, 'payment_time = ""') !== false) {
            $matches[] = $file->getPathname();
        }
    }
}

echo "=== SCAN RESULTS ===\n";
if (empty($matches)) {
    echo "No files found containing strict MySQL mode incompatible empty-string comparisons.\n";
} else {
    echo "Found incompatible empty-string comparisons in the following files:\n";
    foreach ($matches as $match) {
        echo "  - $match\n";
    }
}
echo "=== SCAN END ===\n";
?>
