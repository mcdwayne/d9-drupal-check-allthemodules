Install...
Normal procedure:
1) Place in modules/contrib/jump_menu
2) Enable at admin/build/modules or via drush.
3) Visit the block editing page (or use context/panels/etc) to place one of
   the newly avialble jump menu blocks on the page.
4) Developers: create a custom module which populates a jump menu however
   you like and exposes a block or page callback element.

Menus...
This modules runs from: any exsisting menu, from any parent item, to any depth.
Create the menu you want manually, via nodes, Taxonomy Menu, Auto Menu, etc.
A great place to easily find the menu ID number is the edit link within the
admin/build/menu area.

Placing...
This module will create a jump down block for each menu on your site.
These blocks are created to allow non-developers to make use of this module.
A block will also be available for local menu tasks (like node view/edit/revisions/etc).

Additionally this module can be used by developers to place drop-down menus via
the nice clean output function. Though it's sacrilege, you can just place this
code in a block with PHP Input Format. However what you should do is create a
small module to provide the blocks you need.

Configuration...
Set your "please choose" text (which displays as the first item in the menu)
by setting a custom block title. By default the block title will remain as the
menu name and the menu name will appear as the text within the drop down.
If you set the block title it will remove normal block title display.

Code...
jump_menu($menu, $parent, $btn = false, $maxDepth = 0, $choose = 'Select Now', current = FALSE);

A hard coded specific menu would look something like this:

Drupal 7:
<?php
function MYMODULE_block_info() {
  $menu_name = 'YOUR-MENU-MACHINE-NAME-HERE';
  $blocks = array(
    'MYMODULE' => array(
    'info' => t('My Jump Menu'),
    'cache' => DRUPAL_NO_CACHE,
    ),
  );
  return $blocks;
}

function MYMODULE_block_view($delta = '') {
  if (module_exists('jump_menu')) {
    $data = array(
      'subject' => t('My Jump Menu Title'),
      'content' => jump_menu($menu_name, 0, FALSE, 0, t('-- Select destination --')),
    );
    return $data;
  }
}
?>

Drupal 6:
<?php
function MYMODULE_block($op = 'list', $delta = 0) {
  $menu_name = 'YOUR MENU MACHINE NAME HERE';
  if (module_exists('jump_menu')) {
    switch ($op) {
      case 'list':
        $blocks = array();
        $blocks['mymodule_' . $menu_name]['info'] = t('My Jump Menu');
        $blocks['mymodule_' . $menu_name]['cache'] = BLOCK_NO_CACHE;
        return $blocks;
        break;

      case 'view':
        $data['subject'] = t('My Jump Menu Title');
        $data['content'] = jump_menu($menu_name, 0, FALSE, 0, '-- Select destination --');
        return $data;
        break;
 
    }
  }
}
?>

Recommendations...
This module has been useful for folks looking for alternate mobile menus.
Try hiding this menu for desktop users and exposing it for mobile devices.

Admin drop downs are pretty useful, either based on the Navigation menu or
better yet one created specificly for your various editor roles.

<?php
if (module_exists('jump_menu')) {
  echo jump_menu('navigation', 18, 'Go!', 0, 'Manage the Site');
}
