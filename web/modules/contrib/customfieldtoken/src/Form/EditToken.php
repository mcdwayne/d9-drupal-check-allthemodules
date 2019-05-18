<?php

namespace Drupal\customfieldtoken\Form;

use Drupal\core\Url;
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
class EditToken extends FormBase {

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

    $query = db_select('custom_token', 'ct')
      ->fields('ct', ['field_machine_name', 'max_trim_length', 'token_desc', 'content_type_machine', 'field_machine_id'])
      ->condition('rid', $tokenid)
      ->execute()
      ->fetchAll();

    foreach ($query as $value) {
      $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
      $contentTypesList = [];
    }
    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }
    $form['select_type_submit']['select_type'] = [
      '#type' => 'select',
      '#empty_option' => t('select type'),
      '#required' => TRUE,
      '#disabled' => TRUE,
      '#default_value' => $value->content_type_machine,
      '#options' => $contentTypesList ,
      '#title' => $this->t('Select Type'),
      '#ajax' => [
        'callback' => '::populate_fields',
        'event' => 'change',
        'wrapper' => 'replace_select_div',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Verifying type...'),
        ],
      ],
    ];

    $all_fields = array_keys(\Drupal::service('entity_field.manager')->getFieldDefinitions('node', $value->content_type_machine));
    $base_fields = array_keys(\Drupal::service('entity_field.manager')->getBaseFieldDefinitions('node', $value->content_type_machine));
    $def_fields = array_diff($all_fields, $base_fields);

    $form['select_field'] = [
      '#type' => 'select',
      '#empty_option' => t('select field'),
      '#required' => TRUE,
      '#disabled' => TRUE,
      '#default_value' => $value->field_machine_id,
      '#options' => $def_fields ,
      '#title' => $this->t('Select Field for which you want to generate the Token'),
      '#prefix' => '<div id="replace_select_div">',
      '#suffix' => '</div>',
    ];
    $form['token_desc'] = [
      '#type' => 'textfield',
      '#maxlength' => 50,
      '#size' => 40,
      '#default_value' => $value->token_desc,
      '#required' => TRUE,
      '#title' => $this->t('Token Description'),
    ];
    $form['max_trim_length'] = [
      '#type' => 'number',
      '#default_value' => $value->max_trim_length,
      '#title' => $this->t('Trim Length'),
      '#required' => TRUE,
    ];

    $form['token_edit_id'] = [
      '#type' => 'hidden',
      '#default_value' => $tokenid,
      '#title' => $this->t('token edit id'),
      '#required' => TRUE,
    ];
    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Edit Token'),
    ];
    return $form;

  }

  /**
   * Implements ajax callback for select.
   */
  public function populate_fields(array &$form, FormStateInterface $form_state) : array {
    $all_fields = array_keys(\Drupal::service('entity_field.manager')->getFieldDefinitions('node', $form_state->getValue(['select_type'])));
    $base_fields = array_keys(\Drupal::service('entity_field.manager')->getBaseFieldDefinitions('node', $form_state->getValue(['select_type'])));
    $def_fields = array_diff($all_fields, $base_fields);
    $form['select_field'] = [
      '#type' => 'select',
      '#empty_option' => t('select field'),
      '#required' => TRUE,
      '#options' => $def_fields ,
      '#title' => $this->t('Select Field for which you want to generate the Token'),
      '#prefix' => '<div id="replace_select_div">',
      '#suffix' => '</div>',
    ];

    return $form['select_field'];
  }

  /**
   * Getter method for Form ID.
   */
  public function getFormId() {
    return 'edit_custom_field_token';
  }

  /**
   * Implements a form submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $content_type_machine = $form_state->getValue(['select_type']);
    $content_type_label   = $form['select_type_submit']['select_type']['#options'][$content_type_machine];
    $field_machine_name   = $form['select_field']['#options'][$form_state->getValue('select_field')];
    $max_trim_length      = $form_state->getValue(['max_trim_length']);
    $token_desc           = $form_state->getValue(['token_desc']);

    // Update table on edit.
    $insert_field_token = db_update('custom_token')
      ->fields([
        'content_type_machine' => $content_type_machine,
        'content_type_label'   => $content_type_label,
        'field_machine_name' => $field_machine_name,
        'max_trim_length' => $max_trim_length,
        'token_desc' => $token_desc,
      ])
      ->condition('rid', $form_state->getValue(['token_edit_id']))
      ->execute();
    drupal_set_message(t('your record has been successfully updated'), 'status');
    $form_state->setRedirectUrl(Url::fromUserInput('/admin/token/listing'));
    return;

  }

}
