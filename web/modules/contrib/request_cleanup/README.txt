ABOUT

This module removes URL query parameters coming from Facebook (fbclick), Google (utm_source, utm_medium,
utm_campaign), and any other parameter that can be ignored by Drupal.

This allows internal page cache to store less items, and avoid generating responses for different requests
that internally are handled by the same page controller and parameters.

LIMITATIONS

If you use external page cache like Varnish, and have administrative access to its settings, it is better to remove unneeded parameters there, and avoid hitting Drupal
site completely. See this page https://www.getpagespeed.com/server-setup/varnish/strip-query-parameters-varnish for
the reference.

If you use external page cache, but don't have administrative access to it, it is recommended to keep internal page cache on
and have non-null storage on it, i.e. do NOT uncomment this line in settings.php:

//$settings['cache']['bins']['page'] = 'cache.backend.null'

INSTALLATION

- composer require drupal/request_cleanup

Then enable module either from UI or drush.

CONFIGURATION

By default, module removes the following parameters:

- fbclick
- utm_campaign
- utm_source
- utm_medium

To override this list, add to settings.php this snippet:

$settings['request_cleanup.get'] = ['fbclick', 'utm_source', 'utm_medium', 'utm_campaign', 'yourparameter'];


