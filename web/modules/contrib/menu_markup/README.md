# menu_markup v1.2
Module for Drupal 8 to allow HTML markup in menu titles.

By default, Drupal 8 will not accept HTML markup as part of a menu title. This module allows you to configure markup to be shown along with specific menu titles (including submenu items).

This can be very handy if you want to show a glyphicon from Bootstrap or an icon from FontAwesome, etc. in front of your menu titles.

Additionally, this module has functionality that will easily allow you to put Bootstrap-style "badges" next to your menu items, representing node counts (e.h. "Articles (30)", etc.).

There are similar modules for D7, but they seem to have been abandoned. Further, this module takes the concept a step or two further with functionality.

The latest version can usually be found on GitHub before here [3].

# Requirements
This version of the module only works with the v8.x releases of Drupal.

# Standard usage scenario
1. Download and install the module.
2. Open up the edit page for a menu link
3. Expand the Menu Markup section to see the options
4. Enter in your link markup
5. A special token @title can be used to substitute in the translated original title text
6. If you select a node type count, another special token called @nodeCount will be available representing the total count of published nodes of the selected type (useful for Bootstrap badges in menu items)

# Demo Site
To see a demo, visit the <a href="http://incurs.us" target="_blank">Incursus web site</a> and check out our top navbar menu.

# Example link markup

```
<span class="fa fa-home"></span>&nbsp;&nbsp;@title
```
```
<span class="fa fa-star"></span>&nbsp;&nbsp;<strong>@title</strong> <span class="badge">@nodeCount</span>
```

# Similar/Related Modules

1. <a href="https://www.drupal.org/project/special_menu_items">Special Menu Items</a>
2. <a href="https://www.drupal.org/project/menu_attributes">menu_attributes</a>

Credits / contact
-------------------------------------------------------------------
Currently maintained by Scott Burkett (incursus [1]).

Ongoing development is sponsored by Incursus, Inc. [2]

The best way to contact the authors is to submit an issue, be it a support request, a feature request or a bug report, in the project issue queue here in GitHub.

References
-------------------------------------------------------------------
1. https://www.drupal.org/u/incursus
2. http://incurs.us
3. https://github.com/IncursusInc/menu_markup
