<?php

namespace Drupal\dimension\Plugin\Field;

trait LengthTrait {

  /**
   * @inheritdoc
   */
  public static function fields() {
    return array(
      'length' => t('Length'),
    );
  }

}
