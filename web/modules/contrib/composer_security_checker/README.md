# Composer Security Checker

## Introduction

The Composer Security Manager module will check any installed Composer packages
against the [SensioLabs Security Checker](https://security.sensiolabs.org/)
service, and output a report similar to the core Update Manager report.

When installed using the recommended methods below, it will also install the
[Roave Security Advisories](https://github.com/Roave/SecurityAdvisories) package
to prevent installation of any Composer packages with known vulnerabilities.

## Installation

### Via Composer (recommended)

Note: These instructions are for Drupal 8.1.*. For Drupal 8.0.*, please follow
instructions presented by the Composer Manager module.

1. Follow instructions from https://www.drupal.org/node/2404989 to set your
your site up for Composer managed modules.

2. Run the following command:

```
$ composer require drupal/composer_security_manager
```

### Manually or via Drush/Drupal Console

1. Install the module using whichever method you prefer.

2. In your Drupal root, run the following commands:

```
$ composer require sensiolabs/security-checker ~3.0.0
$ composer require roave/security-advisories dev-master
```

## Troubleshooting

### I've installed everything, but I get a 500 error on the report page

This is normally due to APC caching of the Service Container. Usually,
restarting PHP and/or Apache should fix this.

You can also add `$settings['class_loader_auto_detect'] = FALSE;` in your
`local.settings.php` file, as per [here](http://data.agaric.com/what-do-when-developing-drupal-8-module-and-class-file-just-isnt-being-autoloaded-even-though-it-def)

## Roadmap

These are just some things I'd like to get in at some point. There is no
timescale for their implementation.

* Cache the response based on the hashes in the `composer.lock` file.
* Add e-mail notifications.
* Add documentation for service switching.

## Contribution

As this module is pretty much solely API based, testing is tricky. Any patches
submitted moving forward that don't have direct contact with the SensioLabs
should contain PHPSpec specs. These specs do not follow Drupal Coding
Standards, so if contributing, please ensure the standards as set out in the
spec files is matched.

To run the PHPSpec tests, run `composer install` in the module directory,
and then run `./vendor/bin/phpspec run`.

Any other functionality should have Unit, Kernel, or Functional tests where
appropriate.
