Environmental Config
====================

Environmental_config is a module that helps you automatically importing 
environment specific configuration without any hassle.

It's ideal when you need different settings for a specific module or system
configuration in your development/staging or live server.

It runs with the standard Drupal Configuration Synchronisation and doesn't
require much configuration, in fact there is no UI provided by this module.

### Real life examples of usage

You have your Google Analytics module with a live API key saved in the Drupal
configuration. You don't want to manually change the API key every time you
deploy a new development server.

You have another third-party module that connects with your client's CRM live 
database and you don't want to risk to alter or have access to sensitive data 
on non-live environments.

You have your development bunch of modules that have to bee manually 
re-enabled every time a live db import happens.

Environmental_config can help you by managing environment-based configuration 
files and auto-importing them with the standard Drupal configuration 
synchronisation or through a specific Drupal console command.

It has been designed especially to work in CI environments where there are 
limited options to work with custom system environment variables and settings.

### Similar modules

#### config_split

Config_split provides similar functionality but works with a  
pre-configured profile editable from the UI, it also offers export 
functionality.

Environmental_config doesn't provide a UI, it only needs a valid 
configuration in the settings.php file where to extract the current
environment name. Once the settings are valid its work is based on 
the environment detected and will work without further user 
input (ideal for CI environments).

## REQUIREMENTS
===============

- Config (core module)
- Drupal console

## INSTALLATION
===============

1. Download and install environmental_config module.
2. Export the Drupal configuration in order to have config_environmental_config
 enabled in any configuration import (otherwise on any config synchronisation 
 the module will be disabled and the import will not work as expected).

```bash
cd /path/to/drupal/root
composer require drupal/environmental_config
drupal module:install environmental_config
```

## CONFIGURATION
================

Make sure to have followed the installation steps, especially step number `2`.

Environmental_config needs to know the environment where the website is running
on and the location of the configuration files to import for the current 
environment, if no environment is detected, no override will be applied and the
default configuration will be imported.

In two simple steps:

- Define an environment name, e.g. `dev`
- Save your environment specific configuration files in a specific folder with
the name of the environment, e.g. `sites/default/config/dev/`

Environmental_config will not interact with the export process, so you will 
need to manually place any environmental configuration in the environment 
folder you created.

The following steps will explain multiple ways to define and discover an 
environment name and how to specify a configuration folder for your 
environment.

### Define your environmental configuration folder

To specify the folder where your environmental files are stored, use the 
standard Drupal config_directories array as you would have done for any other
configuration directory.

Assuming that you named your development environment `dev` and you want to 
store all the dev configuration under `sites/default/config/dev`,
just add this line in your `settings.php` file:

```php
$config_directories['dev'] = 'sites/default/config/dev';
```

Note that in the folder `sites/default/config/dev` you will only store
the configuration you want to keep "environment related" and not the full 
website configuration.

### Define an environment name

To specify on which environment the site is running, you have different 
options (and many others can be added via plugin, see developers section):

- Via Settings.php
- Via PHPEnv
- Via custom YML file

#### Via Settings.php

At the bottom of you Settings.php file add the current line:

```php
$settings['environmental_config']
  ['plugins']['settingsfile']['env'] = 'ENVIRONMENT';
```

Replace `YOUR_ENVIRONMENT` with the name of your environment.

#### Via PHPEnv

In your vHost or through CLI just declare the environmental variable 
`ENVIRONMENTAL_CONFIG_ENV` assigning as value the environment name you
want to use.

#### Via custom YML file

Create a .yml file with the filename you wish and add the environment name 
based on the base_url:

Please note that the URL must be defined as output by the `global $base_url`
without any trailing slash or http(s) prefix.

```
sandbox.dev:
  env: dev
  
staging.mysite.it:
  env: staging
```

In the file above we defined that if we are running the site with the URL 
`sandbox.dev` the relative environment name will be `dev`, while on 
`staging.mysite.it` the environment name will be `staging`.

Last step is to let environmental_config know the path of our newly created 
yml file, just add this line at the bottom of your `settings.php` file assuming
that our custom file is placed just outside the docroot folder:

```php
$settings['environmental_config']['plugins']['customfile'] = [
  'environments_file_path' => '/../../../mycustomfile.yml',
];
```

## USAGE
========

You can use environmental_config via:

- Web interface, using drupal config synchronisation
- Drupal console command `config:envimport`

### Via Web interface

Visit the standard drupal page `admin/config/development/configuration`. 
If there are any files overridden you will see a list showing them.

Just click `Import all` to start the import process.

In case your environmental files aren't showing, you can rebuild the temp
folder by clicking `Rebuild environmental configuration folder` or by
clearing the site cache.

### Via Drupal console

You can use the power of environmental_config also by command line by 
running the command `drupal config:envimport` with a few options:

- --custom-env=YOUR_ENV
  - You can manually force to use the given environment rather than 
  leaving the system discover it
- --url=YOUR_BASE_URL.IT
  - You can specify the base_url you want according to the environmental 
  yml file you created
- --fallback=1
  - In case environmental_config should fail to detect the environment 
  the import won't stop and the default Drupal config:import will be run
- --self-debug=1
 - Shows the detected environment only without taking any action

By default, if environmental_config fails to retrieve the environment, 
it won't import anything.

Example of usage:

- drupal config:envimport --custom-env=dev
- drupal config:envimport --url=sandbox.dev

## TROUBLESHOOTING
==================

## FAQ
======

## NOTES
========

Environmental_config only works on the Synchronisation tab (Import all) and not
on the single Import tab.

Please note that environmental_config doesn't act in the export process. You
will need to manually create your environmental configuration files and put in 
your environmental config directory.

## MAINTAINERS
====================

Current maintainers:
 * Alessio De Francesco (aless_io) - https://drupal.org/u/aless_io
 
## DEVELOPERS SECTION
====================

### Need to know

In order to correctly alter the config import from the Web UI, 
Environmental_config needs to override the service `config.storage.staging`. 
As you know, if more than one module is overriding a single service, this can
fall in an unexpected series of unpredictable events so, make sure
you are not using any other module that rewrites the service above.

### Extend the Environment Detector plugin

If you need to create your own environment detector plugin to suit your website
requirements, what you only need to do is create an `EnvironmentDetector` 
plugin that returns an environment string.
By extending the class `EnvPluginBase` you will inherit the correct interface 
to make your job easier.
