<?php

namespace Drupal\pach_examples\Plugin\pach;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\pach\Plugin\AccessControlHandlerBase;

/**
 * Example access control handler plugin for blocks.
 *
 * @AccessControlHandler(
 *   id = "block_example",
 *   type = "block",
 *   weight = -10
 * )
 */
class BlockExample extends AccessControlHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    /* @var $entity \Drupal\block\BlockInterface */
    // Applies to all blocks in region "header".
    return 'header' === $entity->getRegion();
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccessResultInterface &$access, EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    /* @var $entity \Drupal\block\BlockInterface */
    if ('bartik_branding' === $entity->id() && 2 == date('w')) {
      // Hide the branding block in bartik on tuesdays.
      $access = $access->andIf(AccessResult::forbidden());
    }
  }

}
