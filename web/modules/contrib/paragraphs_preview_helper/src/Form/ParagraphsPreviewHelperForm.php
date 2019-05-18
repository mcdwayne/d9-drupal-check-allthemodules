<?php

namespace Drupal\paragraphs_preview_helper\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Paragraphs Preview Helper Form.
 */
class ParagraphsPreviewHelperForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $paragraphs_type = NULL) {
    $config   = \Drupal::configFactory()->getEditable('paragraphs_preview_helper.settings');
    $settings = $config->get('paragraph_types');

    $form['paragraphs_type'] = [
      '#type'             => 'hidden',
      '#value'            => $paragraphs_type,
    ];

    $form['preview_string'] = [
      '#type'             => 'textarea',
      '#title'            => t('Paragraphs Preview String'),
      '#description'      => t('Try to keep this short. Use the token browser below to add field values from the paragraph type.'),
      '#token_types'      => array('paragraph'),
      '#element_validate' => ['token_element_validate'],
      '#default_value'    => isset($settings[$paragraphs_type]) ? $settings[$paragraphs_type] : '',
    ];

    $form['token_tree'] = array(
      '#theme'            => 'token_tree_link',
      '#token_types'      => array('paragraph'),
      '#global_types'     => FALSE,
      '#show_restricted'  => TRUE,
      '#show_nested'      => FALSE,
    );

    $form['actions'] = [
      '#type'             => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type'             => 'submit',
      '#value'            => t('Save changes'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paragraphs_preview_helper_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('paragraphs_preview_helper.settings');

    // Get the settings array and update it with the new paragraph type preview
    // string value.
    $settings = $config->get('paragraph_types');
    if (!isset($settings)) {
      $settings = [];
    }
    $settings[$form_state->getValue('paragraphs_type')] = $form_state->getValue('preview_string');

    $config->set('paragraph_types', $settings)->save();

    drupal_set_message(t('Preview string saved successfully.'));
  }
}