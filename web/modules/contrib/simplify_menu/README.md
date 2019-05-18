CONTENTS OF THIS FILE
---------------------

 * Introduction
 * How to use
 * Requirements
 * Installation
 * How to Use
 * Maintainers


INTRODUCTION
------------

The Simplify Menu module uses a TwigExtension to gain access to Drupal's main
menu's (or any other menu for that matter), render array so it can be accessed
from a twig template. Among the many advantages of having full control of the
menu's render array in a twig template is the ability to customize the markup
for your menus to ensure they are accessible and comply with standards.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/simplify_menu

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/simplify_menu


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Simplify Menu module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


HOW TO USE
----------

```
{# Get menu items #}
{% set items = simplify_menu('main') %}

{# Iterate menu tree #}
<nav class="navigation__items">
  {% for menu_item in items.menu_tree %}
    <li class="navigation__item">
      <a href="{{ menu_item.url }}">{{ menu_item.text }}</a>
    </li>
  {% endfor %}
</nav>
```


MAINTAINERS
-----------

 * Mark Shropshire (shrop) - https://www.drupal.org/u/shrop
 * Jesus Manuel Olivas (jmolivas) - https://www.drupal.org/u/jmolivas
 * Mario Hernandez (mariohernandez) - https://www.drupal.org/u/mariohernandez

Supporting organizations:

 * WeKnow - https://www.drupal.org/weknow
 * Government By Design LLC - https://www.drupal.org/government-by-design-llc
