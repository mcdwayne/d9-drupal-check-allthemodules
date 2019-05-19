Simple Megamenu Bonus
extends the great Simple Megamenu Module (https://www.drupal.org/project/simple_megamenu) by @flocondetoile

Special thanks to @flocondetoile.
If he likes the concept of these extensions we'd be happy to integrate this module into simple_megamenu one day. Otherwise this should be seen as a nice option for your special use-case.
-------
# What it does:
- Extends megamenu item entities with a view mode selector to allow view mode selection for every single mega menu item.
-- Provides a new twig function: view_megamenu_bonus(menu_item, menu_item_below_rendered) which renders the megamenu item in the selected view mode
- Adds the (optional, other) menu items below the parent menu entry which links to the mega menu as computed field to remove the dependency for "before" / "after" viewmode and make them flexibly movable in mega menu field display. This allows for flexible mega menu item (field) layouts and displays. Also allows to flexibly disable output of below menu items in displays.
- TEMP-fixes https://www.drupal.org/node/2917431 ("Add theme suggestion from menu block suggestion field") by hook_theme_suggestions_HOOK_alter. [THIS WILL BE REMOVED IF / AS SOON AS THE PATCH BECOMES PART OF simple_megamenu].
-------
# How to use this module (TO BE CONTINUED):
1. Install simple_megamenu and simple_megamenu_bonus
2. Copy simple_megamenu_bonus/templates/menu--simple-megamenu.html.twig into the "templates" directory of your theme
3. Clear caches
4. Read the documentation of simple_megamenu because we simply extends its logic.
5. Configure your mega menu type(s) and its field displays (e.g. admin/structure/simple_mega_menu_type/megamenu_default/edit/display).
	 Move (to display it in the right place) or disable (if you want to hide them) the "Submenu (menu items below)" field display and your custom fields.
6. Create the different view modes you'd like to use to render or just keep the "default" if you only need one.
(6a. Eventually delete "before" / "after" view mode if you don't need them. The are from simple_megamenu and are not useful in our logic. Anyway they won't have any negative or positive effect, if you keep them.)
7. Create your simple megamenu items (e.g. admin/content/simple_mega_menu) and select the view mode to render each mega menu item in.
8. Enjoy the results and provide feedback ()


-------
# Proudly developed by DROWL.de - Die Drupal CMS Spezialisten aus Ostwestfalen-Lippe (OWL) in NRW. 
https://www.DROWL.de