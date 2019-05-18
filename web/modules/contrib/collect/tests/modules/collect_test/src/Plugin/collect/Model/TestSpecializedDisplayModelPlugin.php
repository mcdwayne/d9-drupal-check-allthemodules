<?php
/**
 * @file
 * Contains \Drupal\collect_test\Plugin\collect\Model\TestSpecializedDisplayModelPlugin.
 */

namespace Drupal\collect_test\Plugin\collect\Model;

use Drupal\collect\Model\SpecializedDisplayModelPluginInterface;
use Drupal\collect\TypedData\CollectDataInterface;

/**
 * Collect model plugin with specialized display, used for testing.
 *
 * @Model(
 *   id = "test_specialized_display",
 *   label = @Translation("Test Model Plugin with specialized display"),
 *   description = @Translation("Used only for testing."),
 *   patterns = {
 *     "https://drupal.org/project/collect/schema/test"
 *   }
 * )
 */
class TestSpecializedDisplayModelPlugin extends TestModelPlugin implements SpecializedDisplayModelPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build(CollectDataInterface $data) {
    return array(
      '#type' => 'item',
      '#markup' => "Let me say " . $data->get('greeting')->getString(),
    );
  }

}
