<?php

/**
 * @file
 * 
 * Drupal\user_reference_node_access\Form\UserReferenceNodeAccessConfigurationForm.
 */

namespace Drupal\user_reference_node_access\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines our form class.
 */
class UserReferenceNodeAccessConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_reference_node_access_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'user_reference_node_access.user_reference_node_access_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('user_reference_node_access.user_reference_node_access_settings');

    $yes_no_options = [
      1 => $this->t('Yes'),
      0 => $this->t('No'),
    ];

    $form['note'] = [
      '#type' => 'item',
      '#markup' => $this->t('
        <ul>
          <li><strong>**Warning:</strong> if you change these settings, existing nodes will need to be resaved to reflect changes</li>
          <li>Admin has full access grants</li>
        </ul>
      '),
    ];

    $form['user_reference_field_name_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic Settings'),
      '#attributes' => [
        'class' => [
          'user-reference-field-name-wrapper',
        ],
      ],
    ];
    $form['user_reference_field_name_wrapper']['user_reference_field_name'] = [
      '#type' => 'textfield',
      '#attributes' => [
        'type' => 'text',
      ],
      '#title' => $this->t('User reference field name (field must exist on node)'),
      '#required' => TRUE,
      '#description' => $this->t('If a node has this field, it will follow the node access settings below.'),
      '#maxlength' => 160,
      '#size' => 60,
      '#default_value' => $config->get('user_reference_field_name'),
    ];

    $form['user_reference_grants_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Referenced Users Settings'),
      '#attributes' => [
        'class' => [
          'user-reference-grants-wrapper',
        ],
      ],
    ];
    $form['user_reference_grants_wrapper']['user_reference_grants_view'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow referenced users to view node?'),
      '#description' => $this->t('<em>Yes</em> will set view grants for all referenced users.'),
      '#required' => TRUE,
      '#options' => $yes_no_options,
      '#default_value' => $config->get('user_reference_grants_view') ?: 1,
    ];
    $form['user_reference_grants_wrapper']['user_reference_grants_edit'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow referenced users to edit node?'),
      '#description' => $this->t('<em>Yes</em> will set edit grants for all referenced users.'),
      '#required' => TRUE,
      '#options' => $yes_no_options,
      '#default_value' => $config->get('user_reference_grants_edit') ?: 0,
    ];
    $form['user_reference_grants_wrapper']['user_reference_grants_delete'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow referenced users to delete node?'),
      '#description' => $this->t('<em>Yes</em> will set delete grants for all referenced users.'),
      '#required' => TRUE,
      '#options' => $yes_no_options,
      '#default_value' => $config->get('user_reference_grants_delete') ?: 0,
    ];

    $form['node_author_grants_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Node Author Settings'),
      '#attributes' => [
        'class' => [
          'node-author-grants-wrapper',
        ],
      ],
    ];
    $form['node_author_grants_wrapper']['node_author_grants_view'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow node author to view node?'),
      '#description' => $this->t('<em>Yes</em> will set view grants for node author.'),
      '#required' => TRUE,
      '#options' => $yes_no_options,
      '#default_value' => $config->get('node_author_grants_view') ?: 1,
    ];
    $form['node_author_grants_wrapper']['node_author_grants_edit'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow node author to edit node?'),
      '#description' => $this->t('<em>Yes</em> will set edit grants for node author.'),
      '#required' => TRUE,
      '#options' => $yes_no_options,
      '#default_value' => $config->get('node_author_grants_edit') ?: 1,
    ];
    $form['node_author_grants_wrapper']['node_author_grants_delete'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow node author to delete node?'),
      '#description' => $this->t('<em>Yes</em> will set delete grants for node author.'),
      '#required' => TRUE,
      '#options' => $yes_no_options,
      '#default_value' => $config->get('node_author_grants_delete') ?: 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    $this->configFactory->getEditable('user_reference_node_access.user_reference_node_access_settings')
      ->set('user_reference_field_name', $values['user_reference_field_name'])
      ->set('user_reference_grants_view', $values['user_reference_grants_view'])
      ->set('user_reference_grants_edit', $values['user_reference_grants_edit'])
      ->set('user_reference_grants_delete', $values['user_reference_grants_delete'])
      ->set('node_author_grants_view', $values['node_author_grants_view'])
      ->set('node_author_grants_edit', $values['node_author_grants_edit'])
      ->set('node_author_grants_delete', $values['node_author_grants_delete'])
      ->save();

    parent::submitForm($form, $form_state);

  }

}
