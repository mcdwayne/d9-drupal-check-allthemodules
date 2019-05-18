<?php

namespace Drupal\developer_suite\Hook;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface HookFormAlterInterface.
 *
 * @package Drupal\developer_suite\Hook
 */
interface HookFormAlterInterface {

  /**
   * Executes the form alter hook.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. The arguments that
   *   \Drupal::formBuilder()->getForm() was originally called with are
   *   available in the array $form_state->getBuildInfo()['args'].
   * @param string $form_id
   *   String representing the name of the form itself. Typically this is the
   *   name of the function that generated the form.
   */
  public function execute(array &$form, FormStateInterface $form_state, $form_id);

}
