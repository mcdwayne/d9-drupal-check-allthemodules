<?php

namespace Drupal\drd\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\drd\Entity\DomainInterface;

/**
 * Provides a 'Remote' block plugin.
 *
 * @Block(
 *   id = "drd_remote_block",
 *   admin_label = @Translation("DRD Remote Block"),
 *   deriver = "Drupal\drd\Plugin\Derivative\RemoteBlock"
 * )
 */
class Remote extends Base {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if (!$this->isDrdContext()) {
      return AccessResult::forbidden();
    }

    $entity = $this->getEntity();
    if (!($entity instanceof DomainInterface)) {
      return AccessResult::forbidden();
    }

    list(, $module, $delta) = explode(':', $this->getPluginId());
    $content = $entity->getRemoteBlock($module, $delta);
    if (empty($content)) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\drd\Entity\DomainInterface $entity */
    $entity = $this->getEntity();
    list(, $module, $delta) = explode(':', $this->getPluginId());

    $build = [];
    $build['remote_block']['#markup'] = $entity->getRemoteBlock($module, $delta);

    return $build;
  }

}
