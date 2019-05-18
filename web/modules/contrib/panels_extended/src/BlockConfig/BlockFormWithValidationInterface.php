<?php

namespace Drupal\panels_extended\BlockConfig;

use Drupal\Core\Form\FormStateInterface;

/**
 * Extends BlockFormInterface to allow form validation.
 */
interface BlockFormWithValidationInterface extends BlockFormInterface {

  /**
   * Adds block type-specific validation for the block form.
   *
   * @param array $form
   *   The form definition array for the block configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateBlockForm(array &$form, FormStateInterface $form_state);

}
