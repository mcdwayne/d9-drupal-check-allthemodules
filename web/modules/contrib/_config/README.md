
Contents of this file
---------------------

 * About this module
 * Usage
 * Examples
 * Demo
 * Installation
 * Notes
 * References/Related Projects


About this Module
-----------------

A simple API that makes it easy to keep custom configuration inside custom 
modules.

Drupal 8's configuration management system requires that all configuration files
be installed and managed using the database. Custom modules usually require
some custom data that probably does not have to be stored in the database, 
especially if the custom data does not need to be edited using Drupal's 
admin UI. Keeping custom data on disk and inside a custom module can 
make it easier to develop and maintain a custom module's configuration, 
as well as allow the custom configuration files to be automatically tracked 
via a version control system like GIT.

Custom configuration that can be managed using `_config`

- Credentials
- Custom messages or help text
- Form options
- Static lookup tables
- Simple PHP arrays


Usage
-----

- All custom config files need to be stored in a `_config` directory inside 
  a custom module.
- All config files in the `_config` directory must be begin with the 
  custom module's namespace. 
- Custom configuration files do support using periods within property/key names.


Examples
--------

The `_config.module` includes an example YAML config file in 

    _config/_config.example.yml 
  
which just contains... 
  
    message: 'Hello'
    
To read the entire file and get a PHP array you can use 

    _config('_config.example');
    
which returns...

    ['message' => 'Hello'];

To get just the 'message' property from the file you can use

    _config('_config.example', 'message');

which returns...
    
    'Hello';

To check that the config file exists you can use

    _config_exists('_config.example');

The `_config` and `config_exists` functions are wrappers to the `_config` 
service that points to `Drupal\_config\_Config` which is very simple class.
The `_config` service can be injected into other services.

This API is intended to be as simple as possible. It is up to you to determine 
if you want to use the `_config` wrapper function instead of the 
`_config` service.


Demo
----

- Enable and examine the `_config_example.module` to see how `_config` files 
  can be used to add help text, alter forms, and add descriptions to roles.

> [Watch a demo](http://youtu.be/p9gIHhZnMIU) of the `_Config` module.

 
Installation
------------

1. Copy/upload the `_config.module` to the modules directory of your Drupal
   installation.
2. Enable the `_Config` module in 'Extend'. (/admin/modules)
3. Check that `_config` YAML files are protected. (/admin/reports/status)
4. Add a `_config` directory to your custom module.


Notes
-----

The `_config` API does not mirror Drupal's configuration management API
because this API is intended to be as simple as possible. 
I am open to discussing having this module's API mirror core's configuration
management API.

Finally, this module is really just a very simple cookbook recipe for Drupal 8 
and you should feel free to just copy the `_config` API into your custom module. 


References/Related Projects
---------------------------

- [Managing configuration in Drupal 8](https://www.drupal.org/documentation/administer/config)
- [Features](https://www.drupal.org/project/features)
- [Config in Code (CINC)](https://www.drupal.org/project/cinc) 


Author/Maintainer
-----------------

- [Jacob Rockowitz](http://drupal.org/user/371407)
