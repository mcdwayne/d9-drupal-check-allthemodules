<?php

namespace Drupal\panels_extended\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\panels\Form\PanelsEditBlockForm;

/**
 * Improvements to the panels edit block form.
 */
class ExtendedPanelsEditForm extends PanelsEditBlockForm {

  use FormValidationFixTrait;

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateFormWithErrorFix($form, $form_state);
  }

}
