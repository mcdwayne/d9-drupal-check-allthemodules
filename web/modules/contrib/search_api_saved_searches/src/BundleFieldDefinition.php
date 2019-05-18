<?php

namespace Drupal\search_api_saved_searches;

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Provides a class for defining entity bundle fields.
 *
 * @see https://www.drupal.org/node/2346347
 *
 * @internal Is expected to be removed in favor of a Core solution at some
 *   point.
 */
class BundleFieldDefinition extends BaseFieldDefinition {

  /**
   * {@inheritdoc}
   */
  public function isBaseField() {
    return FALSE;
  }

}
