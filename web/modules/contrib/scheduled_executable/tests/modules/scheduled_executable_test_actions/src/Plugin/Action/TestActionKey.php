<?php

namespace Drupal\scheduled_executable_test_actions\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * @Action(
 *   id = "scheduled_executable_test_action_name",
 *   label = @Translation("Test action which sets the name of the entity it executes."),
 *   type = "test_entity",
 *   category = @Translation("Testing set name"),
 * )
 */
class TestActionKey extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $state = \Drupal::state();

    $value = \Drupal::state()->get('scheduled_executable_test_action_key');
    if (!is_array($value)) {
      $value = [];
    }
    $value[] = $entity->name->value;

    $state->set('scheduled_executable_test_action_key', $value);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowed();

    return $return_as_object ? $result : $result->isAllowed();
  }

}
