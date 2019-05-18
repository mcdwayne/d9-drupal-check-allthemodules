DYNAMIC PERSISTENT MENU

== Contents of this file ==

 * Summary
 * Example
 * Version history
 * Inner workings

== Summary ==

Displays a menu with one-level of submenus, in a block.

== Example ==

(1) A menu exists with the following structure:

  - Menu A
   - B
    - C
   - D
    - E
   - F
    - G
     - H

(2) You create a dymanic persistent menu in admin/config/user-interface/dynamic_persistent_menu, linking it to Menu A.

(3) Display the block in a region

(4) *With Javascript enabled*: Your new block will display B, D, and F. When hovering over B, C appears below. When hovering over D, E appears. When hovering over F, G appears. H never appears. *With Javascript disabled*, B, D, and F appear, and C, E, G and H never apear. [x-ref: inner workings, below]

(5) You may have to modify your CSS to tweak the display.

== Version history ==

7.x-2.x-dev

- Simpletest automated testing added
- Support for Drupal 7

6.x-2.x-dev

- Support for multiple dynamic persistent menus via dynamic_persistent_menu

6.x-1.x-dev

- Support for Drupal 6

5.x-1.x-dev (obsolete)

- Initial release

== Inner workings ==

Any number of dynamic persistent menus can be created by a user with the correct rights via admin/config/user-interface/dynamic_persistent_menu. Indeed, at least one must be created in order for this module to anything. Each dynamic menu includes a parent element (either a menu or any menu item), and a timeout (how long the submenu remains visible after hovering). Each dynamic menu is stored in a custom database table.

Each dynamic menu produces a block which must be placed in a region (using the core block or context module for example).

Anybody viewing the page will then see the menu(s), having the behavior described above, in section (4) of the section "Example".
