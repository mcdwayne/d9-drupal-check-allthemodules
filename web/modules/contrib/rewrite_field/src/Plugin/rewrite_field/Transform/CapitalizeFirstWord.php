<?php

namespace Drupal\rewrite_field\Plugin\rewrite_field\Transform;

use Drupal\rewrite_field\TransformCasePluginInterface;
use Drupal\Component\Utility\Unicode;

/**
 * Class to transform the string to Capitalize First Word.
 *
 * @TransformCase (
 *   id = "capitalize_first_word",
 *   title = @Translation("Capitalize first word"),
 *   description = "Capitalize the first character of each word."
 * )
 */
class CapitalizeFirstWord implements TransformCasePluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function transform($output) {
    return Unicode::ucwords($output);
  }

}
