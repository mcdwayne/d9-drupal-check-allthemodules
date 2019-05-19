<?php

namespace Drupal\simple_a_b_blocks\Plugin\SimpleABType;

use Drupal\simple_a_b\SimpleABTypeBase;

/**
 * Provides a 'BlockCustom' test.
 *
 * @SimpleABType(
 *   id = "block_custom",
 *   name = @Translation("Custom Block"),
 *   entityTargetType = "block_content",
 *   entityDescription = @Translation("Select a custom block to apply tests
 *   too")
 * )
 */
class BlockCustom extends SimpleABTypeBase {

}
