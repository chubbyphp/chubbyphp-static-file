<?php

$apacheMimeTypes = file_get_contents('https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types');

$mimetypes = [];
foreach (explode(PHP_EOL, $apacheMimeTypes) as $line) {
    $line = trim($line);
    if ('' === $line || str_starts_with($line, '#')) {
        continue;
    }

    $matches = [];
    if (1 !== preg_match('/^([^\t]+)\t+(.*)$/', $line, $matches)) {
        throw new \Exception("Cannot parse line ${line}");
    }

    foreach (preg_split('/\s+/', $matches[2]) as $key) {
        $mimetypes[(string) $key] = $matches[1];
    }
}

if (!isset($mimetypes['yml'])) {
    $mimetypes['yml'] = 'application/x-yaml';
}

if (!isset($mimetypes['jsonx'])) {
    $mimetypes['jsonx'] = 'application/jsonx+xml';
}

ksort($mimetypes);

file_put_contents(__DIR__.'/src/mimetypes.php', '<?php return ' . var_export($mimetypes, true) . ';');
