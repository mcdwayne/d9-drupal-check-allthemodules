<?php

namespace Drupal\cision_feeds\Feeds\Target;

use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a boolean field mapper.
 *
 * @FeedsTarget(
 *   id = "language",
 *   field_types = {
 *     "language"
 *   }
 * )
 */
class Language extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['value'] = trim($values['value']);
  }

}
