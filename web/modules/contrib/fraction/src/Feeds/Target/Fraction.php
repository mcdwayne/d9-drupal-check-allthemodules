<?php

namespace Drupal\fraction\Feeds\Target;

use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;

/**
 * Defines an fraction field mapper.
 *
 * @FeedsTarget(
 *   id = "fraction",
 *   field_types = {
 *     "fraction",
 *   }
 * )
 */
class Fraction extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['value'] = trim($values['value']);
    // Pull out the numerator and denominator.
    $parts = explode('/', $values['value']);

    if (!empty($parts[0]) && is_numeric($parts[0]) && !empty($parts[1]) && is_numeric($parts[1])) {
      $values['numerator'] = $parts[0];
      $values['denominator'] = $parts[1];
    }

    else {
      $values['value'] = '';
      $values['numerator'] = '';
      $values['denominator'] = '';
    }
  }

}

