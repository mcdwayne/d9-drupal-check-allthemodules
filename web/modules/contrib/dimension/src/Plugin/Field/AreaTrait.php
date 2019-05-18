<?php

namespace Drupal\dimension\Plugin\Field;

trait AreaTrait {

  /**
   * @inheritdoc
   */
  public static function fields() {
    return array(
      'width' => t('Width'),
      'height' => t('Height'),
    );
  }

}
