CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration
 * Create custom tag cloud style
 * Troubleshooting
 * Maintainers


INTRODUCTION
------------

The Dynamic Tag Cloud module provides a Tag Cloud based searching of content.
Module provides 2 styles of tag cloud.
    1. Default - Where tag will be simple listed out with simple styling.
    2. Index - Where tag will be indexed, sorted and shown based index selected.

For a full description of the module, visit the project page:
https://www.drupal.org/project/dynamictagclouds

To submit bug reports and feature suggestions, or to track changes:
https://www.drupal.org/project/dynamictagclouds/issues/2950748


INSTALLATION
------------

 * Install the module as you would normally install a contributed Drupal module.

 Visit:  https://www.drupal.org/node/1897420 for directions to installing.

 Visit: https://www.drupal.org/project/dynamictagclouds/git-instructions
  for cloning the project repository.


CONFIGURATION
-------------

Go to Admin >> Structure >> Block layout and place 'Tag cloud block' in desired
region. In tag cloud block you can configure:
    1. Vocabularies - Select which all vocabulary tags should be listed out.
    2. Style - Style of tag cloud.
    3. Redirect url - Set the redirection url, when user click on the tag. Token
     is enabled for this redirection url.


CREATE CUSTOM TAG CLOUD STYLE
-----------------------------

To create custom tag cloud style, please follow the below steps:
    1. In your custom module, create new plugin for TagCloud which inherits
       TagCloudBase class. Or copy paste DefaultTagCloud.php to your custom
       module and rename filename, namespace and class.
    2. Change the following in plugin annotation:
         id - Plugin Id, this should be unique.
         label - Plugin style label.
         libraries - List of libraries name defined in your module libraries.yml
                     file for your custom tag cloud style.
         template - Tag cloud twig template details:
           type - template provider module/theme. In your case it would be
                  'module'
           name - Module/Theme which defines the template. In your case it would
                  be your module name.
           directory - Directory path where twig template resides.
           file - Twig template name excluding '.html.twig'.
    3. Implement your logic in build() method.

Set newly created tag cloud style in tag cloud block configuration and you
are done !!!


TROUBLESHOOTING
---------------

If the module is not shown in the list, try deleting the module and try cloning
it again. Or else try clearing the cache, and then try installing it.


MAINTAINERS
-----------

 * Rakesh James (rakesh.gectcr)(https://www.drupal.org/u/rakeshgectcr)
 * Manoj K (manojapare)(https://www.drupal.org/u/manojapare)
