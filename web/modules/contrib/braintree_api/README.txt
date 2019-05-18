INTRODUCTION

This is an API module to enable using the Braintree API in your custom module.

REQUIREMENTS
------------

  * Key (https://www.drupal.org/project/key)
  * Braintree PHP SDK (https://github.com/braintree/braintree_php)

INSTALLATION
------------

Install using `composer require drupal/braintree_api` in order to pick up the
Braintree SDK dependency.

CONFIGURATION
-------------

  * Follow the links given on the settings form at
    /admin/config/services/braintree_api to find your API credentials.

  * Enter them in the settings form, and save the form.

  * Confirm that the "Sandbox" environment is selected during initial
    development

  * Click the "Test Braintree Connection" to confirm things are working.

  * Inject the `braintree_api.braintree_api` service into your custom class,
    then use the Braintree SDK.

When entering your private key using the Key module, if you're using a file
provider to store your private key be sure to select the option to strip away
line endings, otherwise Webhooks received from Braintree will not validate.

WEBHOOKS
--------

If you are using webhooks, add a webhook in the Braintree console by
navigating to Settings -> Webhooks. Add a new webhook pointing to
https://[your-domain]/braintree/webhooks, and select the events for which
Braintree should send webhooks. During local development you may wish to
tunnel Braintree webhooks to your local machine using ngrok, by entering a
webhook URL of the form
http://[subdomain-provided-by-ngrok].ngrok.io/braintree/webhooks?XDEBUG_SESSION_START=PHPSTORM
into the sandbox Braintree console, where the query parameter starts xdebug
with PHPStorm if you have that configured on your local system.

Subscribe to the BraintreeApiWebhookEvent with an event subscriber. For an
example, see the BraintreeApiWebhookEvent event subscriber in the Braintree
Cashier module.

SETTINGS.PHP
------------

Consider adding $config['braintree_api.settings']['environment'] = 'sandbox';
to your local settings.local.php to ensure you're always using the sandbox
environment locally.

TROUBLESHOOTING
---------------
  * Check your API credentials. If you're storing them in private files,
    ensure that you have selected "strip away line endings" in the Key
    module configuration.

  * See the Braintree Cashier module for an example of how to use Braintree API.

MAINTAINERS
-----------

Current maintainers:
  * Shaun Dychko (ShaunDychko) - https://www.drupal.org/u/shaundychko

This project has been sponsored by:
  * College Physics Answers - Screencast solutions to physics problems in
    the OpenStax College Physics textbook.
    Visit https://collegephysicsanswers.com
