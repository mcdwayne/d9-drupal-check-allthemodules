<?php

/**
 * @file
 * Contains \Drupal\docker\DockerBuildAccessController.
 */

namespace Drupal\docker;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the docker build entity.
 *
 * @see \Drupal\docker\Entity\DockerBuild
 */
class DockerBuildAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'create':
      case 'update':
      case 'delete':
        return $account->hasPermission('administer docker');
        break;
    }
  }

}
