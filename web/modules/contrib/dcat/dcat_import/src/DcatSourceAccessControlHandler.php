<?php

namespace Drupal\dcat_import;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the DCAT Source config entity.
 *
 * @see \Drupal\dcat_import\Entity\DcatSource.
 */
class DcatSourceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowed();
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
