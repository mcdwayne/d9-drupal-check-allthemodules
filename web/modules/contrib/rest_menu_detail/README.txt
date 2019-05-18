CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Rest Menu Detail module is responsible to get Drupal menu links information
as a REST web service response.

 * For a full description of the module visit:
   https://www.drupal.org/project/rest_menu_detail

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/rest_menu_detail


REQUIREMENTS
------------

This module requires a module restui for installation.


INSTALLATION
------------

 * Install the Rest Menu Detail module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module and its
       dependencies.
    2. Navigate to Administration > Configuration > Services > REST and enable
       the Service "Menu detail rest resource". It will provide an end point url
       to call menu service "api/menu_detail/{menuName}". Replace the
       "{menuName}" with your actual menu name and get the response with all
       details of that Menu. Save configuration.
    3. Navigate to Administration > Configuration > Services > Menu REST
       settings.
    4. By default response will include only Menu Title and URI. Select
       parameters that should be part of your REST response. Save configuration.


MAINTAINERS
-----------

 * Kuldeep Kumar (kuldeep_kumar) - https://www.drupal.org/u/kuldeep_kumar
