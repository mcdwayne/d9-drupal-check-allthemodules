<?php

namespace Drupal\entity_base\Controller\Workflow;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Controller routines for entity routes.
 */
class CurrentController extends WorkflowControllerBase implements ContainerInjectionInterface {

  public function setCurrent(EntityInterface $entity) {
    $entity->set('current', TRUE);
    $entity->save();
    return $this->redirect($entity->toUrl('collection')->getRouteName());
  }

}
