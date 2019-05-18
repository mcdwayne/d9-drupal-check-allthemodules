# Fitbit

The Fitbit module wraps the Fitibit API and exposes Fitbit data to views. The 
module is made up of two modules. The first is a base module which serves to 
wrap the Fitbit API. To that end, the following is provided:

* Global settings form for setting a Fitbit application.
* Per-user settings form to enable users of your site to connect their Fitbit
accounts. Users can revoke access at anytime. 
* Services for getting Fitbit data and stored access tokens per user.

The second module is Fitbit Views. It provides a views query plugin to expose 
Fitbit data of all connected users on your site to views. This allows you to 
build views as you're used to, but with data straight from the Fitbit API.

## Installation

Fitbit **must** be installed via Composer, in order to get the required 
libraries. The tarballs are provided on drupal.org for informative purposes 
only.

1. Add the Drupal.org repository if you haven't already

```
composer config repositories.drupal composer https://packages.drupal.org/8
```

This allows Composer to find Fitbit and other Drupal modules.

2. Download Fitbit

```
composer require drupal/fitbit:~1.0
```
This will download the latest release of Fitbit. Use `1.x-dev` instead of `~1.0`
to get the deve release instead. Use 
`composer update drupal/fitbit --with-dependencies` to update Fitbit to a new 
release.

See Using [Composer in a Drupal project](https://www.drupal.org/node/2404989) 
for more information.

### Fitbit module

1. Install the Fitbit (fitbit) module
2. Assign the 'Authorize Fitbit account' permission to any user roles that 
should be allowed to connect their Fitbit accounts. 
3. Configure a Fitbit application by visiting /admin/config/services/fitbit 
and following the directions there.
4. Have your users connect their Fitbit accounts on their Fitbit user settings 
page, eg. /user/1/fitbit.

If you indend on using this module as a library for your own modules/themes 
Fitbit integration, your done. You can now make use of the services provided by
the base module (mainly fitbit.access_token_manager and fitbit.client). See the 
code documentation in those services for more details. If you'd like to use 
views to easily build lists of your users' Fitbit data, read on.

### Fitbit views module

To use views to show connected users' Fitbit data, enable the Fitbit views 
(fitbit_views) sub-module.

1. Install the Fitbit views (fitbit_views) module.
2. Create a new view.
3. Under View Settings, choose one of the Fitbit data types for the 'Show' 
dropdown.
4. Continue as usual to create a view of Fitbit data.
5. Note that you can combine data from different Fitbit data types (
corresponding to the endpoints exposed by Fitbit APIs) by adding a Relationship.
Add a relationship in the usual way to be able to add more data per user.
6. If you know of a Fitbit endpoint that is not covered by the views 
integration, log an issue https://www.drupal.org/project/issues/fitbit.
