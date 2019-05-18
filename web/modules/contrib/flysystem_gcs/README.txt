Flysystem Google Cloud Storage
=============

For setup instructions see the Flysystem README.txt.

## CONFIGURATION ##

Access to the Cloud Storage JSON API must be enabled in the Google API library.

Example configuration:

$schemes = [
  'cloud-storage' => [
    'driver' => 'gcs',
    'config' => [
      'bucket' => 'example',
      'keyFilePath' => '/serviceaccount.json',
      'projectId' => 'google-project-id',
      // More options: https://googlecloudplatform.github.io/google-cloud-php/#/docs/google-cloud/v0.46.0/storage/storageclient?method=__construct
      // Optional local configuration; see https://github.com/Superbalist/flysystem-google-cloud-storage#google-storage-specifics
      '_localConfig' => [
        'prefix' => 'extra-folder/another-folder/',
        'uri' => 'https://cname',
      ],
    ],
    'cache' => true, // Cache filesystem metadata.
  ],
];

$settings['flysystem'] = $schemes;