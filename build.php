<?php

$result = <<<'EOT'
<?php
mb_internal_encoding( 'UTF-8' );
EOT;

foreach (
    array(
        'ecwid_json', 'ecwid_product_api', 'ecwid_catalog', 'ecwid_platform', 'ecwid_misc', 'run'
    ) as $file) {
    $contents = file_get_contents(__DIR__ . '/' . $file . '.php');
    $result .= preg_replace('!^<\?php!', '', $contents);
}

echo $result;
