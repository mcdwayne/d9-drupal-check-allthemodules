# Onlinepbx

Подстановка имени клиента:
```php
<?php
/**
 * Implements hook_onlinepbx_clients_alter().
 */
function HOOK_onlinepbx_clients_alter(&$clients) {
  drupal_set_message(__FUNCTION__);
}
```
