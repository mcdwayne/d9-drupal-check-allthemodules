<?php

namespace Drupal\entity_grants;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Entity manager service.
 */
class GrantManager implements GrantManagerInterface {

  public function getGrants(EntityInterface $entity, $operation, AccountInterface $account, $realm = 'all') {

    $grants_query = \Drupal::entityQuery('entity_grant')
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->condition('uid', $account->id())
      ->condition('grant', $operation);
    if ($realm != 'all') {
      $grants_query->condition('realm', $realm);
    }

    $grants = $grants_query->execute();

    return $grants;
  }

}
