<?php

namespace Drupal\tag1quo\Adapter\Settings;

use Drupal\Core\Site\Settings as CoreSettings;

/**
 * Class Setting8.
 *
 * @internal This class is subject to change.
 */
class Settings8 extends Settings {

  /**
   * {@inheritdoc}
   */
  public function get($name, $default = NULL) {
    return CoreSettings::get($name, $default);
  }

}
