* $node has field_product which references commerce_product entities.
* $products will be an array of those loaded entities, or an empty array.

## Return all products referenced by a node

      $products = data_api('node')->get($node, 'field_product', array(), function ($items, $default) {
          array_walk($items, function (&$item) {
              $item = commerce_product_load($item['product_id']);
          });
    
          return $items ? $items : $default;
      });
