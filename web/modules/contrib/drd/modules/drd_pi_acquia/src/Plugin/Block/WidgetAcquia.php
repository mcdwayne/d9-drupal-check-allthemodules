<?php

namespace Drupal\drd_pi_acquia\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\drd_pi\Plugin\Block\WidgetPlatforms;

/**
 * Provides a 'WidgetPlatforms' block.
 *
 * @Block(
 *  id = "drd_pi_acquia",
 *  admin_label = @Translation("DRD PI Acquia"),
 *  weight = -18,
 *  tags = {"drd_widget"},
 *  account_type = "acquia_account",
 * )
 */
class WidgetAcquia extends WidgetPlatforms {

  /**
   * {@inheritdoc}
   */
  protected function title() {
    return $this->t('Acquia');
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'drd.administer');
  }

  /**
   * {@inheritdoc}
   */
  protected function content() {
    return $this->t('@table<p><a href="@link">Settings</a></p>', [
      '@table' => $this->entitiesTable(),
      '@link' => (new Url('drd_pi_acquia.drd_pi_acquia_settings'))->toString(),
    ]);
  }

}
