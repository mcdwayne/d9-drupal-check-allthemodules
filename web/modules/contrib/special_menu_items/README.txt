Special Menu Items Module
------------------------

Description
-----------
Special Menu Items is module that enables placeholder menu items. Placeholder is a menu item which is
actually not a link. Something like this is useful with drop down menus where we want to have a parent link which
is actually not linking to a page but which is just acting as a parent grouping some children below it.

Features
--------
  - User can create a new menu item and leave out the link.
  - When the menu is rendered the "nolink" item will be rendered similar to a normal menu link item, but there will
    be no link, just the title.
  - Breadcrumb of "<nolink>" will be rendered same as "<nolink>" menu item.

Installation
------------
1. Copy the special_menu_items folder to your modules directory.
2. At Administer -> Extend (admin/modules) enable the module.
3. Place the menu.html.twig in your theme's templates folder.

Upgrading
---------
Just overwrite (or replace) the older special_menu_items folder with the newer version.