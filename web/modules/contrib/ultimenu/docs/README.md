
# ABOUT ULTIMENU
Ultimenu is the UltimatelyDeadSimple&trade; megamenu ever with dynamic region
creation.

An Ultimenu block is based on a menu.
Ultimenu regions are based on the menu items.

The result is a block contains regions containing blocks, as opposed to: a
region contains blocks.

The module manages the toggle of Ultimenu blocks, regions, and a skins library,
while leaving the management of block, menu and regions to Drupal.

At individual Ultimenu block, you can define a unique skin and the flyout
orientation.

You don't have to write regions in the theme .info, however you can always
permanently store resulting region definitions in it.


## FEATURES at 2.x
* Ajaxified Ultimenu regions, suitable for massive menu contents.
* Off-canvas menu, mobile only by default. Yet, configurable for both mobile and
  desktop under **Ultimenu goodies** section.
* Iconized titles.


## FEATURES
1. Multiple instance of Ultimenus based on system and menu modules.
2. Dynamic regions based on menu items which is toggled without touching .info.
3. Render menu description.
4. Menu description above menu title.
5. Add title class to menu item list.
6. Add mlid class to menu item list.
7. Add menu counter class.
8. Remove browser tooltip.
9. Use mlid, not title for Ultimenu region key.
10. Custom skins, or theme default "css/ultimenu" directory for auto discovery.
11. Individual flyout orientation: horizontal to bottom or top, vertical to
    left or right.
12. Pure CSS3 animation and basic styling is provided without assumption.
    Please see and override the ultimenu.css for more elaborate positioning,
    either left, centered or right to menu list or menu bar.
13. With the goodness of blocks and regions, you can embed almost anything:
    views, panels, blocks, menu_block, boxes, slideshow..., except a toothpick.

All 1-9 is off by default.


## WHY ANOTHER MEGAMENU?
I tried one or two, not all, and read some, but found no similar approach.
Unless I missed one. Please file an issue if any similar approach worth a merge.


## HOW CAN YOU HELP?
Please consider helping in the issue queue, provide improvement, or helping with
documentation. Thanks!


## RELATED MODULES
* [OM Maximenu](http://drupal.org/project/om_maximenu)
* [Megamenu](http://drupal.org/project/megamenu)
* [Superfish](http://drupal.org/project/superfish)
* [Menu Views](http://drupal.org/project/menu_views)
* [MuchoMenu](http://drupal.org/project/1077858)
* [Giga Menu](http://drupal.org/project/gigamenu)
* [Menu Minipanels](http://drupal.org/project/menu_minipanels)
* [Mega Dropdown](http://drupal.org/sandbox/ravigupta/1099796)
* [Menu Attach Block](http://drupal.org/project/menu_attach_block)


## AUTHOR/MAINTAINER/CREDITS
* [Gaus Surahman](https://drupal.org/user/159062)
* [Committers](https://www.drupal.org/node/1897426/committers)
* CHANGELOG.txt for helpful souls with their patches, suggestions and reports.


## DISCLAIMERS
Like the rest of CSS provided by the module, they are meant basic, not final,
to get up and running quickly. It is not the module job to match your
design requirements. It is your own, or your themer's job to make it awesome.

Every theme has different classes and structures. Your site menu contents may
vary, each menu item may or may not contain regions and their blocks. Unless you
hire me I have no idea about it. It is all yours.

No supports will be provided for CSS issues. You either have to learn CSS
yourself if DIY, or hire a themer, if you have no time. I hope you see this from
the bright side. Patches are welcome, including CSS fixes, if you think
it would benefit others.

Feel free to get in touch with me for paid customizations.
