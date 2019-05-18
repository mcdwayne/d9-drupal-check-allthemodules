<?php

namespace Drupal\cloudwords\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Adds Cloudwords translatable to Project.
 *
 * @Action(
 *   id = "cloudwords_translatable_add_to_project",
 *   label = @Translation("Add translatable to project"),
 *   type = "cloudwords_translatable"
 * )
 */
class CloudwordsTranslatableAddToProject extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    //$entity_values = $entity->toArray();
  }

  /**
   * {@inheritdoc}
   */

  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    //$result = $object->status->access('edit', $account, TRUE)
    //  ->andIf($object->access('update', $account, TRUE));
    //@TODO need to check permissions
    return true;
    //return $return_as_object ? $result : $result->isAllowed();
  }

}
