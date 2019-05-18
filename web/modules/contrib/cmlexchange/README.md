# Обмен данными с 1С
 * Страница отладки выгружаемых товаров: /cmlexchange/orders (Админ -> Конфигурация-> Веб Службы)

```
/**
 * Implements hook_cmlexchange_orders_query_alter().
 */
function HOOK_cmlexchange_orders_query_alter(&$orders) {
  drupal_set_message(__FUNCTION__);
}

/**
 * Implements hook_cmlexchange_orders_xml_alter().
 */
function HOOK_cmlexchange_orders_xml_alter(&$xml) {
  drupal_set_message(__FUNCTION__);
}

```
