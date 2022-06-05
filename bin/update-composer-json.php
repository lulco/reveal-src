<?php

declare(strict_types=1);

// update composer.json version in file
// before: "php": "^8.1"
// after: "php": ">=7.2"

// usage: php bin/update-composer-json.php packages/some-package/composer.json

$composerJsonFilePath = $argv[1];

$composerJsonFile = file_get_contents($composerJsonFilePath);
$composerJsonFile= str_replace('"php": ">=8.1"', '"php": ">=7.2"', $composerJsonFile);

file_put_contents($composerJsonFilePath, $composerJsonFile);

echo sprintf('PHP version in %s file was updated to 7.2' . PHP_EOL, $composerJsonFilePath);
