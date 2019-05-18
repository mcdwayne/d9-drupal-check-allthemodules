Flysystem Rackspace
===================

For setup instructions see the Flysystem README.txt.

## CONFIGURATION ##

Example configuration:

$schemes = [
  'rackspaceexample' => [
    'type' => 'rackspace',
    'config' => [
      'username' => 'bob',
      'password' => 'Super secret password',
    ],

    // Optional.
    'cache' => TRUE, // Cache filesystem metadata.
  ],
];

$settings['flysystem'] = $schemes;
