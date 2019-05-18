<?php

namespace Drupal\fillpdf;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the FillPDF form entity.
 *
 * @see \Drupal\fillpdf\Entity\FillPdfForm.
 */
class FillPdfFormAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
      case 'update':
      case 'duplicate':
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer pdfs');
      default:
        return AccessResult::neutral();
    }
  }

}
