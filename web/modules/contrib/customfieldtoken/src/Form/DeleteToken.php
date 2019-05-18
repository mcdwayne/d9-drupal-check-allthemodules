<?php

namespace Drupal\customfieldtoken\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the SimpleForm form controller.
 *
 * This example demonstrates a simple form with a singe text input element. We
 * extend FormBase which is the simplest form base class used in Drupal.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class DeleteToken extends FormBase {

  /**
   * Build the simple form.
   *
   * A build form method constructs an array that defines how markup and
   * other form elements are included in an HTML form.
   *
   * @param array $form
   *   Default form array structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object containing current form state.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $tokenid = []) {

    $form['token_edit_id'] = [
      '#type' => 'hidden',
      '#default_value' => $tokenid,
      '#title' => $this->t('token edit id'),
      '#required' => TRUE,
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Delete'),

    ];
    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('back'),
      '#submit' => ['::previousform'],
    ];
    return $form;

  }

  /**
   * Implements ajax callback for select.
   */
  public function populate_fields(array &$form, FormStateInterface $form_state) {

    return $form['select_field'];
  }

  /**
   * Getter method for Form ID.
   */
  public function getFormId() {
    return 'eidt_custom_field_token';
  }

  /**
   * Implements a form submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $num_deleted = db_delete('custom_token')
      ->condition('rid', $form_state->getValue(['token_edit_id']))
      ->execute();
    drupal_set_message(t('your record has been successfully deleted'), 'status');
    $form_state->setRedirectUrl(Url::fromUserInput('/admin/token/listing'));
    return;

  }

  /**
   *
   */
  public function previousform(array &$form, FormStateInterface $form_state) {

    $form_state->setRedirectUrl(Url::fromUserInput('/admin/token/listing'));
    return;

  }

}
