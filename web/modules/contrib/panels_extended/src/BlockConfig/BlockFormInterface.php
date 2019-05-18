<?php

namespace Drupal\panels_extended\BlockConfig;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface to add configuration settings to the block form.
 */
interface BlockFormInterface {

  /**
   * Add / modify the block form.
   *
   * @param array $form
   *   The form definition array for the block configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function modifyBlockForm(array &$form, FormStateInterface $form_state);

  /**
   * Adds block type-specific submission handling for the block form.
   *
   * @param array $form
   *   The form definition array for the full block configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitBlockForm(array &$form, FormStateInterface $form_state);

}
