# Piwik Reporting API

This module provides an API that can be used by developers to retrieve data from
the Piwik open source web analytics platform.

## Requirements

- The [Piwik](https://drupal.org/project/piwik) module.
- The [Piwik Reporting API](https://github.com/pfrenssen/piwik-reporting-api)
  PHP library.
- A Piwik server.

## Usage

- Install and enable the Piwik Reporting API module.
- Configure the Piwik module at `/admin/configure/system/piwik`. The Site ID and
  at least one server URL needs to be configured.
- Configure the Piwik Reporting API module at
  `/admin/configure/system/piwik_reporting_api`. You need to enter your user
  authentication token. You can find this in the web interface of the Piwik
  server at Administration > Platform > API.
- Request a `Query` object from the `PiwikQueryFactory` service, passing it
  the name of the Piwik Reporting API method you would like to use:

```
$query_factory = \Drupal::service('piwik.query_factory');

// Example: retrieve the version of the Piwik server.
$query = $query_factory->getQuery('API.getPiwikVersion');
$piwik_version = $query->execute()->getResponse()->value;

// Example: retrieve browser usage statistics for the past week.
$response = $query_factory->getQuery('DevicesDetection.getBrowsers')
  ->setParameter('date', 'today')
  ->setParameter('period', 'week')
  ->execute()
  ->getResponse();
```

For the full list of available methods, see the [Piwik API
reference](https://developer.piwik.org/api-reference/reporting-api).

## Example

An example implementation is provided in the `piwik_reporting_api_example`
module.

## Security

### Use HTTPS for secure communication with the server

The Piwik Reporting API uses a user authentication token to access the API. The
API is very powerful and allows to view, edit and delete most data, even add and
remove users. This means that *the user authentication token should be kept as
secret as your username and password*.

Since the token is sent over the network *it is crucial that all network traffic
to the Piwik API is encrypted*. You can use an unencrypted HTTP URL during
testing, but for a production server *it is highly recommended to use HTTPS* or
it will be trivial for an attacker to steal your credentials and lock you out of
your Piwik server.

### Securing the user authentication token

Typically the user authentication token is entered through the configuration
form and stored in configuration. However there might be circumstances where
you do not want to expose your token in config:

- The project's source code (including exported configuration) might be open
  source, for example if you are building a distribution.
- The project's source code might be stored on infrastructure provided by a
  third party such as Github.
- The project's source code might be shared with third parties such as external
  development teams.

In these cases you might want to leave the configuration empty, and limit
access to the token by adding the following line to `settings.local.php` on the
production environment:

```
$config['piwik_reporting_api.settings']['token_auth'] = 'e0f8f4151357ffdb56430be8ab59dca8';
```

Also make sure to uncomment the section in `settings.php` titled "Load local
development override configuration".
