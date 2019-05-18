INTRODUCTION
------------

  * Elastic Path (ep) allows users to integrate elastic path with Drupal.


REQUIREMENTS
------------

  * Drupal Commerce.

INSTALLATION
------------

  * With the code installation complete, you must now configure ep to use
    your elastic path credentials. To do so, store them in the $settings
    array in your site's settings.php file (sites/default/settings.php), like
    so:
    $settings['ep.cortex_username'] = 'YOUR Elastic Path Username';
    $settings['ep.cortex_password'] = 'YOUR Elastic Path Password';
    $settings['ep.aws_api_id'] = 'YOUR AWS API ID';
    $settings['ep.aws_api_key'] = 'YOUR AWS API Key';

  * Configure your settings for Elastic path (including your store name)
    at /admin/commerce/config/ep.

CONFIGURATION
-------------

  * Visit the admin/commerce/config/ep/settings page and set your elastic path settings.
