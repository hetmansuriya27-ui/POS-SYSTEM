<?php
function searchInDirectory($dir, $pattern) {
    $it = new RecursiveDirectoryIterator($dir);
    foreach (new RecursiveIteratorIterator($it) as $file) {
        if ($file->isDir()) continue;
        $ext = pathinfo($file->getPathname(), PATHINFO_EXTENSION);
        if ($ext !== 'php' && $ext !== 'html' && $ext !== 'js') continue;
        
        $content = file_get_contents($file->getPathname());
        if (stripos($content, $pattern) !== false) {
            echo "Found in: " . $file->getPathname() . PHP_EOL;
            
            // Print the lines containing the pattern
            $lines = explode("\n", $content);
            foreach ($lines as $i => $line) {
                if (stripos($line, $pattern) !== false) {
                    echo "  Line " . ($i + 1) . ": " . trim($line) . PHP_EOL;
                }
            }
        }
    }
}

echo "=== Searching for 'member' in adminSide ===" . PHP_EOL;
searchInDirectory(__DIR__ . '/../adminSide', 'member');

echo PHP_EOL . "=== Searching for 'membership' in adminSide ===" . PHP_EOL;
searchInDirectory(__DIR__ . '/../adminSide', 'membership');

echo PHP_EOL . "=== Searching for 'member' in customerSide ===" . PHP_EOL;
searchInDirectory(__DIR__ . '/../customerSide', 'member');
?>
