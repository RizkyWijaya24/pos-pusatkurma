<?php
foreach (['harian', 'mingguan', 'bulanan'] as $filter) {
    $file = __DIR__ . "/dashboard_$filter.html";
    if (!file_exists($file)) {
        echo "$file does not exist.\n";
        continue;
    }
    
    $doc = new DOMDocument();
    @$doc->loadHTML(file_get_contents($file));
    $xpath = new DOMXPath($doc);
    $nodes = $xpath->query('//*[@id="breakdown-table-container"]');
    
    if ($nodes->length === 0) {
        echo "Filter $filter: #breakdown-table-container NOT FOUND\n";
    } else {
        $node = $nodes->item(0);
        $path = [];
        $curr = $node;
        while ($curr) {
            $tagName = $curr->nodeName;
            $class = '';
            $id = '';
            if ($curr instanceof DOMElement) {
                $class = $curr->hasAttribute('class') ? $curr->getAttribute('class') : '';
                $id = $curr->hasAttribute('id') ? $curr->getAttribute('id') : '';
            }
            $path[] = $tagName . ($id ? "#$id" : "") . ($class ? " (" . substr($class, 0, 30) . ")" : "");
            $curr = $curr->parentNode;
        }
        echo "Filter $filter: " . implode(" -> ", array_reverse($path)) . "\n";
    }
}
