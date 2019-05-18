<?php

/**
* @file
* Contains \Drupal\usasearch\Plugin\RulesAction\AddToIndex.
*/
namespace Drupal\usasearch\Plugin\RulesAction;

//use Drupal\rules\Core\RulesActionBase;
//use Drupal\Core\Node;
use Drupal\Core\Entity\EntityInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Add To Index' action.
 *
 * @RulesAction(
 *   id = "rules_usasearch_addtoindex",
 *   label = @Translation("Add node to index"),
 *   category = @Translation("DigitalGovSearch"),
 *   context = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the entity, which should be added to the index.")
 *     )
 *   }
 * )
 */
class AddToIndex extends RulesActionBase {

  protected function doExecute(EntityInterface $entity) {
    kint('add to index');

//    $entity->delete();
  }


  /**
   * Executes the Plugin.
   *
   * @param mixed $data
   *   Original value of an element which is being updated.
   * @param mixed $value
   *   A new value which is being set to an element identified by data selector.
   */
//  protected function doExecute($data, $value) {
//    $typed_data = $this->getContext('data')->getContextData();
//    $typed_data->setValue($value);
//  }

//  /**
//   * {@inheritdoc}
//   */
//  public function autoSaveContext() {
//    // Saving is done at the root of the typed data tree, for example on the
//    // entity level.
//    $typed_data = $this->getContext('data')->getContextData();
//    $root = $typed_data->getRoot();
//    $value = $root->getValue();
//    // Only save things that are objects and have a save() method.
//    if (is_object($value) && method_exists($value, 'save')) {
//      return ['data'];
//    }
//    return [];
//  }
}
