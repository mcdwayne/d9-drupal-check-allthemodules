[![Build Status](https://travis-ci.org/mediamonks/drupal-protect-before-launch.svg?branch=8.x-1.x)](https://travis-ci.org/mediamonks/drupal-protect-before-launch)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mediamonks/drupal-protect-before-launch/badges/quality-score.png?b=8.x-1.x)](https://scrutinizer-ci.com/g/mediamonks/drupal-protect-before-launch/?branch=8.x-1.x)
[![Code Coverage](https://scrutinizer-ci.com/g/mediamonks/drupal-protect-before-launch/badges/coverage.png?b=8.x-1.x)](https://scrutinizer-ci.com/g/mediamonks/drupal-protect-before-launch/?branch=8.x-1.x)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)

# Drupal Protect Before Launch

Protected your website before launch with HTTP basic authentication.
The module allows you to set a username and password and enable and
disable them on the fly.

## System Requirements

You need:

- **PHP >= 5.6.0**
- **Drupal >= 8.2**

## Installation

Install with **composer require drupal/protect_before_launch** and enable from your
admin panel or use drush: **drush en protect_before_launch -y**.

## Security

If you discover any security related issues, please email devmonk@mediamonks.com instead of using the issue tracker.

## Configuration

The configuration form can be found in the admin interface:

_Admin_ > _Configuration_ > _Development_ > _Protect Before Launch_.

### Options
The module allows you to set the following options:

* Protection
* Realm
* Denied Content
* Authentication Type
* Username
* Password
* Exclude Paths
* Environment Key
* Environment Value

### Protection

Can be disabled, enabled or be controlled by the environment.

When it is set to "Auto Enabled by Environment" the module will
look for the environment variable and when this is set it will
enable the password protection.

When also the Environment Value is set the the module will check
if the value in the variable is set matches before enabling.

### Realm

This is the message that appears in the authentication box of the browser.

### Denied Content

This is the content that will be outputted to the user if credentials can't be
validated if the site is currently under protection.

### Authentication Type

The module allows you to use the Drupal user database to authenticate
against an existing user or use a username and password.
This username and password  stored is independent from the Drupal user database.

Default username and password is set to:

**username:** username

**password:** password

*Make sure you change these when using the built-in authentication option!*

### Exclude paths

The exclude paths option allows you to set (pcre) patterns.
When the url matches an exclude path for that path protection will be disabled.

## Acquia Hosting

By default the environment key is set to "AH\_NON\_PRODUCTION" to work correctly 
with [Acquia Hosting](https://www.acquia.com/). Set the protection mode to 
"Auto Enabled by Environment" and all non-production servers will be 
password protected automatically.

## Console commands

These console commands are available when using [Drupal Console](https://drupalconsole.com/):

**protect\_before\_launch:protect [disabled | enabled | environment]**<br />
Enable / Disable the password protection

**protect\_before\_launch:username [\<username\>]**<br />
Set the username to authenticate against.

**protect\_before\_launch:password [\<password\>]**<br />
Set the password to authenticate against.

**protect\_before\_launch:environment [\<key\>] [\<value\>]**<br />
Set the environment environment key and environment value

**protect\_before\_launch:environment [\<key\>] --no-value**<br />
Set the environment environment key without environment value

## License

The GPL version 2. Please read more about [Licensing in Drupal](https://www.drupal.org/about/licensing).
