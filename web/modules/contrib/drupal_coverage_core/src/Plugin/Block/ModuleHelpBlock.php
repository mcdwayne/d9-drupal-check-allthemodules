<?php
/**
 * @file
 * Contains
 */

namespace Drupal\drupal_coverage_core\Plugin\Block;


use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Provides a 'Help' block that displays information on code coverage.
 *
 * @Block(
 *   id = "drupal_coverage_core_module_help_block",
 *   admin_label = @Translation("Module Coverage Information"),
 * )
 */
class ModuleHelpBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $html = '<button type="button" class="btn btn-primary">Primary</button>';

    return array(
      '#type' => 'markup',
      '#markup' => $html,
    );
  }

}
