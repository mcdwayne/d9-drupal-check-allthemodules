# Navigation Blocks

_Thanks for taking the time to check this readme!_

## INTRODUCTION

This module adds some navigational blocks, such as a generic back button, which 
allows users to implement a back button that does more then just go back go the 
previous page, as is the default behaviour for a back button in a browser.
Currently, this module defines three back buttons:

 * [Referer](#referer)
 * [Entity](#entity)
 * [Generic](#generic)

## REQUIREMENTS

This module has a requirement on the blocks and link module provided by 
Drupal Core.

## INSTALLATION

Install this module as any other Drupal module, see the documentation on
[Drupal.org](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).

## CONFIGURATION

This module provides three blocks that can be configured to define the behavior
of the back button. More detailed information can be found below in the section
[Button types](#button-types)

## Button types
### Referer
The referer back button works as follows: at the time of placing the block in a 
region, the user can set some configuration. The user can define some URL's, 
just like setting the visibility of a block through URL's, to which they'd like 
the back button to go to. At time of loading the page, the class will match the
referer (the previously visited page), to the defined URL's. If there's a match,
the back button will take the user back to the referer. Just like with setting 
the visibility of a block, wildcards can be used.

### Entity
The entity back button allows user to implement a back button that takes 
visitors back to the canonical URL of an entity. No configuration is required 
to do this. The class will determine the entity currently being viewed through
route parameters. The class also extends the referer back button class, so you
can optionally configure what entities the back button should be generated for 
through URL's.

### Generic
The generic back button can be configured by supplying a URL to which the back 
button should lead to. This class also extends the referer back button, so 
optional configuration can be set.
