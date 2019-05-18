Flysystem Dropbox
=================

For setup instructions see the Flysystem README.txt.

## CONFIGURATION ##

Example configuration:

$schemes = [
  'dropboxexample' => [
    'driver' => 'dropbox',
    'config' => [
      'token' => 'a-long-token-string',
      'client_id' => 'You Client Id Name',

      // Optional.
      'prefix' => 'a/sub/directory',
      'public' => TRUE, // Serve files directly via Dropbox.
    ],
  ],
];

$settings['flysystem'] = $schemes;
