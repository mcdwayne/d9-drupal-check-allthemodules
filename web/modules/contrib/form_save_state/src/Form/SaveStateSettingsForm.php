<?php

namespace Drupal\form_save_state\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class SaveStateSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_save_state_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'form_save_state.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('form_save_state.settings');

    $form_ids = array(
      'user_login_form' => 'User login',
      'user_pass' => 'Reset password',
    );

    // Add form_ids of all currently known node types too.
    $node_forms = \Drupal\node\Entity\NodeType::loadMultiple();
    foreach ($node_forms as $type => $name) {
      $form_ids['node_' . $type . '_form'] = 'Node add form: ' . $type;
      $form_ids['node_' . $type . '_edit_form'] = 'Node edit form: ' . $type;
      //$form_ids['comment_node_' . $type . '_form'] = 'comment_node_' . $type . '_form';
    }

    $for_textarea = array();
    $checked = array();
    $saved_forms = $config->get('form_ids');
    if (!is_array($saved_forms)) {
      $saved_forms = [];
    }

    foreach ($saved_forms as $key => $value) {
      if (in_array($key, array_keys($form_ids))) {
        if ($saved_forms[$key]) {
          $checked[$key] = $key;
        }
      }
      else {
        $for_textarea[$key] = $key;
      }
    }

    $form['form_ids'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Forms'),
      '#options' => $form_ids,
      '#default_value' => $checked,
      '#description' => t('Select the form IDs that you want to enable Autosave.'),
    );

    $form['additional_form_ids'] = array(
      '#type' => 'textarea',
      '#title' => t('Other Forms:'),
      '#default_value' => implode("\n", $for_textarea),
      '#description' => t('Add the form IDs you want to include.'),
    );

    // TODO: implement time interval for form state save.
//    $form['time'] = array(
//      '#type' => 'textfield',
//      '#title' => t('Time (in Seconds)'),
//      '#default_value' => variable_get('form_save_state_time', 15),
//      '#size' => 60,
//      '#maxlength' => 128,
//      '#description' => t('The time interval between each form state save'),
//    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_ids = $form_state->getValue('form_ids');
    $additional_form_ids = explode("\n", $form_state->getValue('additional_form_ids'));
    foreach ($additional_form_ids as $additional_form_id) {
      $additional_form_id = trim($additional_form_id);
      if (!empty($additional_form_id)) {
        $form_ids[$additional_form_id] = $additional_form_id;
      }
    }

    $config = \Drupal::service('config.factory')
      ->getEditable('form_save_state.settings');


    $config->set('form_ids', $form_ids)
      ->save();

    parent::submitForm($form, $form_state);
  }
}