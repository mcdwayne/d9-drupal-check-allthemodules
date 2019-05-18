<?php

namespace Drupal\scheduled_executable_test_actions\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * @Action(
 *   id = "scheduled_executable_test_action_simple",
 *   label = @Translation("Test action"),
 *   type = "test_entity",
 *   category = @Translation("Testing"),
 * )
 */
class TestAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $state = \Drupal::state();
    $state->set('scheduled_executable_test_action', 'executed');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowed();

    return $return_as_object ? $result : $result->isAllowed();
  }

}
