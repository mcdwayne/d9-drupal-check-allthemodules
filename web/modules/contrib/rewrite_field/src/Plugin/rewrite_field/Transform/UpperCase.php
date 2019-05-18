<?php

namespace Drupal\rewrite_field\Plugin\rewrite_field\Transform;

use Drupal\rewrite_field\TransformCasePluginInterface;
use Drupal\Component\Utility\Unicode;

/**
 * Class to transform the string to Uppercase.
 *
 * @TransformCase (
 *   id = "uppercase",
 *   title = @Translation("Upper Case"),
 *   description = "Change output to upper case."
 * )
 */
class UpperCase implements TransformCasePluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function transform($output) {
    return Unicode::strtoupper($output);
  }

}
