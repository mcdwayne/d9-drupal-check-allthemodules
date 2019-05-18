Flysystem SFTP
==============

For setup instructions see the Flysystem README.txt.

## CONFIGURATION ##

Example configuration:

$schemes = [
  'sftpexample' => [
    'driver' => 'sftp',
    'config' => [
      'host' => 'example.com',
      'username' => 'username',
      'password' => 'password', // Only one of 'password' or 'privatekey' is needed.
      'privateKey' => 'path/to/or/contents/of/privatekey',
      'root' => '/path/to/root',

      // Optional
      'port' => 21,
      'timeout' => 10,
    ],
    'cache' => TRUE, // Cache filesystem metadata.
  ],
];

$settings['flysystem'] = $schemes;
