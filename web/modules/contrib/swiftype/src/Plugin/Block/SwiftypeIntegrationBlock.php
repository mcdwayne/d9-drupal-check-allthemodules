<?php
/**
 * @file
 * Contains \Drupal\swiftype_integration\Plugin\Block\SwiftypeIntegrationBlock.
 */

namespace Drupal\swiftype_integration\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Swiftype integration' block.
 *
 * @Block(
 *   id = "swiftype_integration_block",
 *   admin_label = @Translation("Swiftype integration"),
 *   category = @Translation("Forms")
 * )
 */
class SwiftypeIntegrationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'search content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!empty(\Drupal::config('swiftype_integration.settings')->get('swiftype_integration_install_key'))) {
      return \Drupal::formBuilder()
        ->getForm('\Drupal\swiftype_integration\Form\SwiftypeIntegrationSearchForm');
    }
    return array(
      '#markup' => $this->t('No valid Swiftype install key were entered.'),
    );
  }

}
