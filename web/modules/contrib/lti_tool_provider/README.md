# LTI Tool Provider

## INTRODUCTION

The LTI tool provider module provides an LTI authentication provider for Drupal, as well as configuration options for managing LTI consumers, user provisioning, attribute mapping, and default entity provisioning.

Currently it supports LTI v1.0 and v1.1.

## REQUIREMENTS

* PHP 7
* OAuth PECL extension [http://php.net/manual/en/book.oauth.php](http://php.net/manual/en/book.oauth.php)

## INSTALLATION

* Install the OAuth PECL extension as per: [http://php.net/manual/en/oauth.installation.php](http://php.net/manual/en/oauth.installation.php)
* Install the module as per: [https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules)
* Optionally, install the lti_tool_provider_attributes, lti_tool_provider_roles, or lti_tool_provider_provision submodules.

## CONFIGURATION

### General Settings

You most likely will need to enable 'Allow iFrame embeds' in order for LTI consumers to be able to embed your site within an iframe. Because this has security implications it is not turned on by default. As a caveat, the xframe header is only removed when receiving authenticated LTI requests, so there should be minimal security risk.

### LTI Consumers

You can view and add consumers at admin/config/lti-tool-provider/consumer. Once you've added a consumer, you will need to give the consumer key and secret to the LMS or LTI Consumer so that they can add you as a LTI Tool Provider. In order for authentication to work, the LMS need to make public the name and email field.

The LTI url should be at the 'lti' Drupal internal path, e.g. 'https://example.com/lti'.

If you would like to change which name and mail field is used during user provisioning, you can do that when you create the consumer, using the 'Name' and 'Mail' field. Otherwise just leave them as the default, which is 'lis_person_contact_email_primary'.

### User Provisioning

During the LTI launch request, the authentication handler will use the LTI name and mail fields to either find an existing user, or create a new user. After user provisioning is finished, that user will be logged in to Drupal.

If you'd like to sync the LTI roles or LTI attributes to Drupal user roles or attributes, you can enable the relevant submodule.

Please be aware that roles and attributes are synced every time a user launches an LTI request. If the user is an Instructor in one course, and a Learner in another course, the user's Drupal role will be switched to Learner when they log in from that course. Make sure that this is the expected behavior before enabling and configuring these modules.

The most common roles that you will want to sync will be 'urn:lti:role:ims/lis/Learner' and 'urn:lti:role:ims/lis/Instructor'.

### Entity Provisioning

If the lti_tool_provider_provision module is enabled, you can configure a default entity to be automatically created or loaded on each LTI launch request. You can also configure default field values to be mapped from the LTI launch reqeust data.

### Custom Parameters

Currently the only custom parameter that is processed it the custom_destination parameter. You will need to add this in the LMS using 'destination={some internal drupal path}'.

### Cross Domain and SSL Connections

Most LMS now require that https is used for LTI authentication. However it should be possible to authenicate via HTTP, but it is not recommended.

### Module Integration

If you would like to alter the LTI launch, user provisioning, or LTI return, you can do this using the hooks as documented in the lti_tool_provider.api.php file. Also the LTI context variables are available per user via the private temp store. For example:

```php
$context = \Drupal::service('tempstore.private')->get('lti_tool_provider')->get('context');
```
