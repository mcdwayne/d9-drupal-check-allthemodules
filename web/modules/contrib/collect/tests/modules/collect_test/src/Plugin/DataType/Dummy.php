<?php
/**
 * @file
 * Contains \Drupal\collect_test\Plugin\DataType\Dummy.
 */

namespace Drupal\collect_test\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * Dummy datatype.
 *
 * @DataType(
 *   id = "dummy",
 *   label = @Translation("Dummy"),
 * )
 */
class Dummy extends StringData {

}
