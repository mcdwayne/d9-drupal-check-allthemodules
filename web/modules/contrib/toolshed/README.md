# The Portland Webworks Toolshed

A common set of utilities that are often reused for building Drupal sites.

Toolshed sets conventions for handling:

  + Third party settings forms
  + Common blocks (navigation, administration)
  + Menu link resolution
  + Javascript event handlers (media query events, passive events, etc...)
  + Javascript widgets (responsive pager, accordions, etc...)
  + Common Drupal render elements and admin UI building tools.

The module aims to be helpful and reduce the need to rebuild common components
or features that get reused often.


## INSTALLATION

Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/node/895232 for further information.


## TOOLS

Toolshed provides the following tools to a Drupal installation to assist with
building interactive pages and administration forms. The *Toolshed Menu* and
*Toolshed Media* modules both make use of the core *Toolshed*

### Third Party Settings Form plugins

Toolshed provides a plugin API for creating third party settings forms
components for *\Drupal\Core\Config\Entity\EntityConfigInterface* add and edit
forms to support third party settings forms. Plugin discovery looks for the
classes that have the `ThirdPartyFormElements` annotation in the
`plugins/toolshed/ThirdPartyFormElements` folder.

Examples can be found in the *Toolshed menu* and *Toolshed media* module.

### Render Elements

  The following render and form elements:

  + *css_class* - Form element for validating and entry of CSS classes.

### Javascript Utilities

  Toolshed provides the following libraries (toolshed.libraries.yml):

  + *toolshed/screen-events* - Registers a common onResize, onScroll and on
    breakpoint events.
  + *toolshed/dock* - Library for docking of toolbars to the window.
  + *toolshed/accordions* - Behavior and library for creating simple accordions.
  + *toolshed/pager* - Interactive pager for controlling navigation of items.

### Submodules

  + *Toolshed Menu* - Adds `menu_resolver` plugins and a navigation block.
  + *Toolshed Media* - Provides additional media field formatter, and redirect
    directly to file for


## MAINTAINERS

This project has been created and is maintained by:

  + Portland Webworks

Current maintainers:

 + Liem Khuu (lemming) - https://drupal.org/u/lemming
 + Adam Kempler (akempler) - https://drupal.org/u/akempler
 + Joe Cardella (joebot) - https://drupal.org/u/joebot
