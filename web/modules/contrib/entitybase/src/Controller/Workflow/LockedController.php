<?php

namespace Drupal\entity_base\Controller\Workflow;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Controller routines for entity routes.
 */
class LockedController extends WorkflowControllerBase implements ContainerInjectionInterface {

  public function lock(EntityInterface $entity) {
    $entity->set('locked', TRUE);
    $entity->save();
    return $this->redirect($entity->toUrl('collection')->getRouteName());
  }

  public function unlock(EntityInterface $entity) {
    $entity->set('locked', FALSE);
    $entity->save();
    return $this->redirect($entity->toUrl('collection')->getRouteName());
  }

}
