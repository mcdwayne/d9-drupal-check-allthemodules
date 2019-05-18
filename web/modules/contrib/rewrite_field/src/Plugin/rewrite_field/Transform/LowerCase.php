<?php

namespace Drupal\rewrite_field\Plugin\rewrite_field\Transform;

use Drupal\rewrite_field\TransformCasePluginInterface;
use Drupal\Component\Utility\Unicode;

/**
 * Class to transform the string to Lowercase.
 *
 * @TransformCase (
 *   id = "lowercase",
 *   title = @Translation("Lower Case"),
 *   description = "Change output to lower case."
 * )
 */
class LowerCase implements TransformCasePluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function transform($output) {
    return Unicode::strtolower($output);
  }

}
