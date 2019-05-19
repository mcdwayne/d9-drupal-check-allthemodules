# TRAILING SLASH DRUPAL MODULE

## What is it?

Adds trailing slashes to all URLs you want.
For example: example.com/user/.
This feature could be usefull for SEO motivations.

## How do I install it?

Install and enable this module using one of the following methods:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules

When you enable this module you haven't it in action yet. 

Now you have to configure it. Read Configuration section for it.

## Configuration

* To configure the module go to /admin/config/trailing-slash/settings
* On this page you have the option to enable/disable the configuration of this module
* List of paths 
  * Write a path per line where you want a trailing slash. Paths start with slash. (e.g., '/book')
* Enabled entity types
  * You can choose the entity types that you want to have a slash, for example, the taxonomy terms of a particular vocabulary or nodes of a bundle 

## Requirements

 * drupal::language
 * php:7.1
 
## Supporting organizations

 [idealista](https://github.com/idealista/) Sponsorship of ongoing development.
 
## Known Issues/Bugs

 None.
 
 If you found a new one, please, open it at https://www.drupal.org/node/add/project-issue/trailing_slash
