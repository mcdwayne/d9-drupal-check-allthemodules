<?php

/**
 * Drupal\node_access_timestamp_by_user\Form\NodeAccessTimestampByUser.
 */

namespace Drupal\node_access_timestamp_by_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines our form class.
 */
class NodeAccessTimestampByUser extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_access_timestamp_by_user_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'node_access_timestamp_by_user.node_access_timestamp_by_user_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('node_access_timestamp_by_user.node_access_timestamp_by_user_settings');

    // Get enabled content types.
    $nodeTypes = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();

    // Build options array of enabled content types.
    $options = [];
    foreach ($nodeTypes as $nodeType) {
      $options[$nodeType->id()] = $nodeType->label();
    }

    // Build yes no options array.
    $yes_no_options = [
      'yes' => t('Yes'),
      'no' => t('No'),
    ];

    $form['node_access_timestamp_by_user_heading'] = [
      '#type' => 'item',
      '#markup' => t('<h2>Available Node Access Timestamp by User Settings</h2>'),
      '#weight' => -10,
    ];

    $form['content_types_wrapper'] = [
      '#type' => 'fieldset',
      '#weight' => 1,
      '#title' => 'Restrict by Content Types',
      '#attributes' => [
        'class' => [
          'content-types-wrapper',
        ],
      ],
    ];

    $form['content_types_wrapper']['filter_content_types'] = [
      '#type' => 'radios',
      '#title' => 'Filter by Content Types',
      '#required' => TRUE,
      '#description' => 'Run tracking for enabled content types only',
      '#options' => $yes_no_options,
      '#default_value' => $config->get('filter_content_types') ?: 'no',
    ];

    $form['content_types_wrapper']['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => 'Enable Content Types',
      '#required' => FALSE,
      '#description' => 'Choose which content types will write to the database.',
      '#options' => $options,
      '#default_value' => $config->get('content_types'),
      '#states' => [
        'visible' => [
          ':input[name="filter_content_types"]' => ['value' => 'yes'],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->configFactory->getEditable('node_access_timestamp_by_user.node_access_timestamp_by_user_settings')
      ->set('filter_content_types', $values['filter_content_types'])
      ->set('content_types', $values['content_types'])
      ->save();

    parent::submitForm($form, $form_state);

  }

}
