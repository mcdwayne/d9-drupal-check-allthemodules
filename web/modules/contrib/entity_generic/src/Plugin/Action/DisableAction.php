<?php

namespace Drupal\entity_generic\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_generic\Entity\SimpleInterface;

/**
 * @Action(
 *   id = "entity_generic:disable_action",
 *   label = @Translation("Disable entity"),
 *   deriver = "Drupal\entity_generic\Plugin\Action\Derivative\DisableActionDeriver",
 * )
 */
class DisableAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->setStatus(SimpleInterface::ENTITY_DISABLED);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
