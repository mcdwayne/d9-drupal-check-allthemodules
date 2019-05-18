Flysystem ZIP
=============

For setup instructions see the Flysystem README.txt.

## CONFIGURATION ##

Example configuration:

$schemes = [
  'zipexample' => [
    'type' => 'zip',
    'config' => [
      'location' => '/path/to/archive.zip',

      // Optional.
      'prefix' => 'prefix/inside/zip',
    ],
  ],
];

$settings['flysystem'] = $schemes;
