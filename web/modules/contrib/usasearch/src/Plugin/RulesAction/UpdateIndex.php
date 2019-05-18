<?php

/**
* @file
* Contains \Drupal\usasearch\Plugin\RulesAction\AddToIndex.
*/
namespace Drupal\usasearch\Plugin\RulesAction;

//use Drupal\rules\Core\RulesActionBase;
//use Drupal\Core\Node;
use Drupal\Core\Entity\EntityInterface;
//use Drupal\node\NodeInterface;
use Drupal\rules\Core\RulesActionBase;
//use Drupal\usasearch;

/**
 * Provides a 'Update Index' action.
 *
 * @RulesAction(
 *   id = "rules_index_update",
 *   label = @Translation("Update index"),
 *   category = @Translation("DigitalGovSearch"),
 *   context = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the entity, which should be updated in the index.")
 *     )
 *   }
 * )
 */
class UpdateIndex extends RulesActionBase {

  /**
   * Deletes the Entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The entity to be deleted.
   */
  protected function doExecute(EntityInterface $entity) {
    $entity->delete();
  }

}
