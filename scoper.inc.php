<?php

declare(strict_types=1);
/** @var Symfony\Component\Finder\Finder $finder */
$finder = Isolated\Symfony\Component\Finder\Finder::class;
return [
    'prefix' => null,
    'output-dir' => 'build',
    // The 'finders' section tells PHP-Scoper which files to process.
    'finders' => [
        // This finder includes all PHP files in your plugin's 'src' directory
        // and all files within the entire 'vendor' directory. This ensures
        // everything is copied to the build folder.
        $finder::create()
            ->files()
            ->in([
                './src',
                './vendor/azrideus',
            ]),
    ],
];
