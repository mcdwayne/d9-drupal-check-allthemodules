## About TUS

This is the Drupal 8 integration for [tus-php](https://github.com/ankitpokhrel/tus-php).

###How to install:
1. Download & enable this module via composer
2. Ensure these headers in your CORS services.yml settings:
'upload-checksum', 'upload-concat', 'upload-key', 'upload-length', 'upload-metadata', 'upload-offset', 'location', 'tus-checksum-algorithm', 'tus-extension', 'tus-max-size', 'tus-resumable', 'tus-version'
3. If you are using a custom TUS upload client, ensure it passes these values in the header Upload-Metadata (example values given):
entityType: 'node',
entityBundle: 'article',
fieldName: 'field_image'


###Future plans:
- An Uppy file upload widget in separate project: https://www.drupal.org/project/uppy

###Project page:
[drupal.org project page](https://www.drupal.org/project/tus)

###Maintainers:
+ Joshua Walker (@drastik) drupal.org/u/drastik
