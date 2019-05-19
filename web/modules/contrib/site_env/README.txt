/**
 * Site Instances module for Drupal (Site Instances)
 * Compatible with Drupal 8.x
 *
 * By Rahul Gore(https://www.drupal.org/u/rahulgore) 
 */

CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

This module is helpful for developers. Many times it needs to restrict code to be functional on development site only os staging site only. This can be achived with this module.
So once configured you can make your code run on any of the environment Development, Staging or Production(Live).

INSTALLATION
------------

Install as you would normally install a contributed Drupal module.
For help regarding installation, visit:
https://www.drupal.org/documentation/install/modules-themes/modules-8

CONFIGURATION
-------------

How to use this module
Admin URL - http://yoursites.com/admin/config/environment
select the Environment/Instance.
In code e.g. in hook_preprocess or theme_preprocess_html put the code:
function yourtheme_preprocess_html(&$variables) {
  $config = \Drupal::config('environment.settings');
  $site_env = $config->get('siteinstance');
  //set variable for use in Twig
  $variables['site_env'] = $site_env;
}
In twig file:
{% if site_env == 'dev' %}
  your code here for dev site only
{% endif%}
MAINTAINERS
------------

Current Maintainers for Drupal 8 version:
*Rahul V Gore (rahulgore) - https://www.drupal.org/u/rahulgore


