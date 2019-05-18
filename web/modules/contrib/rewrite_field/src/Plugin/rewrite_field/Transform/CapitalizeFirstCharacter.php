<?php

namespace Drupal\rewrite_field\Plugin\rewrite_field\Transform;

use Drupal\rewrite_field\TransformCasePluginInterface;
use Drupal\Component\Utility\Unicode;

/**
 * Class to transform the string to Capitalize First Character.
 *
 * @TransformCase (
 *   id = "capitalize_first",
 *   title = @Translation("Capitalize first character"),
 *   description = "Change output to upper case."
 * )
 */
class CapitalizeFirstCharacter implements TransformCasePluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function transform($output) {
    return Unicode::ucfirst($output);
  }

}
