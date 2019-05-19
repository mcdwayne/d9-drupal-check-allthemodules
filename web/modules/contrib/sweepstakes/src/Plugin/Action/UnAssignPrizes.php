<?php

namespace Drupal\Sweepstakes\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;

/**
 * Removes assigned prizes from the selected entries.
 *
 * @Action(
 *   id = "sweepstakes_unassign_prizes",
 *   label = @Translation("Un-assign prizes from users"),
 *   type = "node"
 * )
 */
class UnAssignPrizes extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, \Drupal\Core\Session\AccountInterface $account = NULL, $return_as_object = FALSE) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $entity->prize_id = $entity->confirmed = 0;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

  }

}
