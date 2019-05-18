<?php

namespace Drupal\panels_extended\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a trait to fix form validation for panels block forms.
 *
 * @internal Only to be used for fixing the validation on the panels block add/edit forms.
 */
trait FormValidationFixTrait {

  /**
   * Block form validation fix to keep the errors in the form state.
   *
   * Overrides PanelsBlockConfigureFormBase::validateForm.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see PanelsBlockConfigureFormBase::validateForm()
   */
  public function validateFormWithErrorFix(array &$form, FormStateInterface $form_state) {
    // The page might have been serialized, resulting in a new variant
    // collection. Refresh the block object.
    $this->block = $this->getVariantPlugin()->getBlock($form_state->get('block_id'));

    $settings = (new FormState())->setValues($form_state->getValue('settings'));
    // Call the plugin validate handler.
    $this->block->validateConfigurationForm($form, $settings);

    // Start fix validation.
    // Check if the form has errors and copy them into the $form_state.
    if (FormState::hasAnyErrors()) {
      foreach ($settings->getErrors() as $name => $error) {
        $keys = explode('][', $name);
        $element = $form['settings'];
        foreach ($keys as $key) {
          $element = $element[$key];
        }
        $form_state->setError($element, $error);
      }
    }
    // End fix validation.
    // Update the original form values.
    $form_state->setValue('settings', $settings->getValues());
  }

}
