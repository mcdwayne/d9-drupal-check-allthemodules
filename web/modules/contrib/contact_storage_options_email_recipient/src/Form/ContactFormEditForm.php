<?php

namespace Drupal\contact_storage_options_email_recipient\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\contact\ContactFormEditForm as contactContactFormEditForm;

/**
 * Class ContactFormEditForm.
 *
 * @package Drupal\contact_storage_options_email_recipient\Form
 */
class ContactFormEditForm extends contactContactFormEditForm {

  /**
   * The contact form.
   *
   * @var \Drupal\contact\Entity\ContactForm
   */
  protected $contactForm;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $this->contactForm = $this->entity;
    $form = parent::form($form, $form_state);

    $optionsEmailField = $this->getRecipientOptionsEmailField();

    if ($optionsEmailField !== FALSE) {
      // We have a contact_storage_options_email field which determines the
      // recipient.
      if ($optionsEmailField->isRequired() === TRUE) {
        // It is required, so we can remove it.
        \Drupal::messenger()
          ->addWarning('The recipient of this form is determined by the \'' . $optionsEmailField->getLabel() . '\' field.');
        unset($form['recipients']);
        $form['recipients']['#required'] = FALSE;
      }
      else {
        // Not required.
        \Drupal::messenger()
          ->addWarning('An optional additional recipient of this form is determined by the \'' . $optionsEmailField->getLabel() . '\' field.');
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $optionsEmailField = $this->getRecipientOptionsEmailField();

    if ($optionsEmailField === FALSE || !$optionsEmailField->isRequired()) {
      return parent::validateForm($form, $form_state);
    }

    // Make validation pass by using a dummy e-mail address and remove it
    // afterwards.
    $form_state->setValue('recipients', 'dummy@dummy.com');
    parent::validateForm($form, $form_state);
    $form_state->setValue('recipients', []);
  }

  /**
   * Gets the contact_storage_options_email field from this form.
   *
   * @return \Drupal\field\Entity\FieldConfig|bool
   *   A FieldConfig instance or FALSE when the form does not have a
   *   contact_storage_options_email field.
   */
  protected function getRecipientOptionsEmailField() {
    $entityManager = \Drupal::service('entity_field.manager');
    $fields = $entityManager->getFieldDefinitions('contact_message', $this->contactForm->id());

    foreach ($fields as $field) {
      if ($field->getType() === 'contact_storage_options_email') {
        return $field;
      }
    }

    return FALSE;
  }

}
