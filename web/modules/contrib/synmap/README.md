# synmap
 * Yandex Map Integration.

 DEVELOPMENT
 -------------
 1. HOOK Map display ALTER (see Drupal\synmap\Hook\PageAttachments::hook().
  * `&$display` - TRUE/FALSE
  * `&$attach` - Map place $.before()

 ```
 /**
  * Implements hook_synmap_display_alter().
  */
 function HOOK_synmap_display_alter(&$display, &$attach) {
   if (!$display && is_object($node = \Drupal::request()->attributes->get('node'))) {
     $display = $node->getType() == 'usluga' ? TRUE : FALSE;
   }
 }
 ```
