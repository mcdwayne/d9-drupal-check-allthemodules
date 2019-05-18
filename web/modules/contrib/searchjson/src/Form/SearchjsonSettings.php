<?php

namespace Drupal\search_json\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Search json export setting form.
 */
class SearchjsonSettings extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_json_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $form['description'] = [
      '#markup' => '<p>' . $this->t('This page allow you to export the json file using view URL') . '</p>',
    ];
    $form['JsonURL'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Export view json URL'),
      '#description' => '<p>' . $this->t('Please enter the view json URL like http://example.com/view_example_json') . '</p>',
    ];
    $form['JsonURL']['url'] = [
      '#title' => $this->t('URL'),
      '#type' => 'textfield',
      '#required' => TRUE,
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export Json File'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $json_url = $form_state->getValue(['JsonURL', 'url']);
    $response = file_get_contents($json_url);
    file_save_data($response, "public://search_json.json", FILE_EXISTS_REPLACE);
    drupal_set_message($this->t('json data has been exported successfully!'));
  }

}
