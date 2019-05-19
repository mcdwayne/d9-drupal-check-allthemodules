***
***

# UPDATING
Ultimenu 2.x is a major rewrite to update for Drupal 8.6+, and add new features.
It may not be compatible with 1.x.
Ultimenu 2.x added few more services, so it may break the site temporarily
mostly due to the introduction of new services. If you do drush, this is no
issue.

* Have backup routines.
* Test it out at a DEV environment.
* Be sure to run **/update.php**, or regular `drush updb` and `drush cr`.

If not updating, simply ignore.

The following are changes, in case you are updating from 1.x.


## NOTABLE CHANGES
- Renamed **active-trail** LI class to **is-active-trail** to match core:
  https://www.drupal.org/node/2281785
- Renamed **js-ultimenu-** classes to **is-ultimenu-** so to print it in
  HTML directly not relying on JS, relevant for the new off-canvas menu.
- Added option to selectively enable ajaxified regions.
- Cleaned up CSS sample skins from old browser CSS prefixes. It is 2019.
- Added off-canvas menu to replace old sliding toggle approach.
- Split `ultimenu.css` into `ultimenu.hamburger.css` + `ultimenu.vertical.css`.
- Added support to have a simple iconized title, check out STYLING.


## FYI CHANGES
The following are just FYI, not really affecting the front-end, except likely
temporarily breaking the site till proper `drush cr` mentioned above.

1. UltimenuManager is split into 3 services: UltimenuSkin, UltimenuTool,
   UltimenuTree services for single responsibility, and to reduce complexity.
2. Ultimenu CSS files are moved from module **/skins** folder to **css/theme**.
3. Moved most logic to `#pre_render` to gain some performance.
4. Old `template_preprocess_ultimenu()` loop is also merged into `#pre_render`.
