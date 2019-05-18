
OVERVIEW
========


              --*-- Drupal 8 version - work in progress --*--


At the moment configuration can only be done in settings.php:

The default configuration (and what is defaulted to when no configuration
is preset) is:

$config['radioactivity.storage'] = [
    'type' => 'default',
];

If you wish to use the provided file rest service (endpoints/file/rest.php),
use the following configuration:

$config['radioactivity.storage'] = [
    'type' => 'rest_local',
];

If you want to run the service under another path (e.g. directly under the root)
or on a different host, you can override the endpoint url as shown below:

$config['radioactivity.storage'] = [
    'type' => 'rest_remote',
    'endpoint' => 'http://www.example.com/rest.php',
];

NOTE! When using a different host name you may need to set CORS (Cross-origin
resource sharing) for things to work properly.
