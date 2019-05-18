CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Console interface

INTRODUCTION
------------
RenderViz makes the cache properties of Drupal's render array output accessible by visualising them.

RenderViz module is currently work in progress. Please contribute! https://www.drupal.org/project/renderviz

INSTALLATION
------------
 * Enable the renderviz module.

CONSOLE INTERFACE
-----------------
The only interface to the module is the developer console of your browser. Help text is displayed in the console when you refresh the page. The following commands are available:

 * renderviz(metadataType, metadataValue)      Query for render data.
 * rendervizFocus(index)                       Focus one result from the query result set.

Usage examples:
 * renderviz('contexts', 'timezone')           Select all elements that have a 'timezone' context.
 * renderviz('tags', 'node:1')                 Select all elements that have the 'node:1' tag.
 * renderviz('max-age', '-1')                  Select all elements that have infinite cache life time.
 * rendervizFocus(0)                           Set focus on the first element found with renderviz().
 * rendervizFocus(1)                           Move focus to the second element.

Note that the console interface is a TEMPORARY measure!