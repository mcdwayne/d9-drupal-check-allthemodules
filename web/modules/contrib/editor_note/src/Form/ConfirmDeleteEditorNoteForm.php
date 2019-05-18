<?php

namespace Drupal\editor_note\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfirmDeleteEditorNoteForm.
 */
class ConfirmDeleteEditorNoteForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'confirm_delete_editor_note_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['use_ajax_container']['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Are you sure you want to remove the note? This action cannot be undone.'),
    ];


    $form['cancel'] = [
      '#type' => 'button',
      '#title' => $this->t('Cancel'),
      '#value' => $this->t('Cancel'),
      '#weight' => '0',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }

  }

}
