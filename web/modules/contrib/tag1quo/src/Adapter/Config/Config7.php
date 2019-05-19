<?php

namespace Drupal\tag1quo\Adapter\Config;

/**
 * Class Config7.
 *
 * @internal This class is subject to change.
 */
class Config7 extends Config6 {

  /**
   * {@inheritdoc}
   */
  public function delete() {
    global $conf;
    db_delete('variable')->condition('name', $this->name . '_', 'LIKE')->execute();
    cache_clear_all('variables', 'cache_bootstrap');
    foreach (array_keys($conf) as $name) {
      if (strpos($name, $this->name . '_') === 0) {
        unset($conf[$name]);
      }
    }
    return $this;
  }

}
