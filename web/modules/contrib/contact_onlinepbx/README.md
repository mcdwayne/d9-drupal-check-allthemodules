# Contact Onlinepbx

Altering $from
```
/**
 * Implements hook_contact_onlinepbx_from_alter().
 */
function HOOK_contact_onlinepbx_from_alter(&$from) {
  drupal_set_message(__FUNCTION__);
}
```
