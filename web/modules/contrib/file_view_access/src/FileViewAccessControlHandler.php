<?php

namespace Drupal\file_view_access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\file\FileAccessFormatterControlHandlerInterface;
use Drupal\file\FileAccessControlHandler;

/**
 * Provides a File access control handler.
 */
class FileViewAccessControlHandler extends FileAccessControlHandler implements FileAccessFormatterControlHandlerInterface {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Check is the file permission exist or not.
    if ($operation == 'view') {
      // Check view access enabled.
      $view_access = $entity->get('view_access')->value;
      if (is_numeric($view_access) && $view_access) {
        if (\Drupal::service('file_system')->uriScheme($entity->getFileUri()) === 'public') {
          return AccessResult::allowedIfHasPermission($account, 'file view access');
        }
      }
      else {
        return parent::checkAccess($entity, $operation, $account);
      }
    }
  }

}
