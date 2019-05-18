<?php

namespace Drupal\atinternet\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

class AtinternetConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'atinternet_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'atinternet.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('atinternet.settings');

    $form['smarttag'] = [
      '#type' => 'details',
      '#title' => $this->t('SmartTag settings'),
      '#open' => TRUE,
      '#description' => $this->t('You can manage the attachment of your smarttag by yourself or you upload your SmartTag or define an external URL here.'),
    ];

    $form['smarttag']['smarttag_manual'] = [
      '#title' => $this->t('I want to manage my smarttag.js by myself'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('smarttag_manual'),
    ];

    $form['smarttag']['smarttag_file'] = [
      '#title' => $this->t('SmartTag file'),
      '#type' => 'managed_file',
      '#description' => $this->t('Upload your AT Internet SmartTag file here.'),
      '#default_value' => $config->get('smarttag_file'),
      '#upload_location' => 'public://atinternet/',
      '#upload_validators' => array(
        'file_validate_extensions' => array('js')
      ),
    ];

    $form['smarttag']['smarttag_url'] = [
      '#title' => $this->t('SmartTag URL'),
      '#type' => 'url',
      '#description' => $this->t('Paste the URL of your SmartTag file here.'),
      '#default_value' => $config->get('smarttag_url'),
    ];

    // ---

    $form['tracking_scope'] = array(
      '#title' => $this->t('Tracking scope'),
      '#type' => 'vertical_tabs',
    );

    $form['page_visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Pages'),
      '#open' => TRUE,
      '#group' => 'tracking_scope',
    ];

    $options = [
      t('Every page except the listed pages'),
      t('The listed pages only'),
    ];
    $form['page_visibility_settings']['visibility_request_path_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add tracking to specific pages'),
      '#options' => $options,
      '#default_value' => $config->get('visibility_request_path_mode'),
    ];

    $form['page_visibility_settings']['visibility_request_path_pages'] = [
      '#type' => 'textarea',
      '#description' => t('If empty, all pages will be tracked. Specify pages by using their paths. Enter one path per line. The \'*\' character is a wildcard.'),
      '#default_value' => $config->get('visibility_request_path_pages'),
      '#rows' => 10
    ];

    $visibility_user_role_roles = $config->get('visibility_user_role_roles');

    $form['role_visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Roles'),
      '#group' => 'tracking_scope',
    ];

    $form['role_visibility_settings']['visibility_user_role_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add tracking for specific roles'),
      '#options' => [
        t('Add to the selected roles only'),
        t('Add to every role except the selected ones'),
      ],
      '#default_value' => $config->get('visibility_user_role_mode'),
    ];
    $form['role_visibility_settings']['visibility_user_role_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => !empty($visibility_user_role_roles) ? $visibility_user_role_roles : [],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names()),
      '#description' => $this->t('If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked (or excluded, depending on the setting above).'),
    ];

    // ---

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $form['advanced']['default_level2'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'level2',
      '#title' => $this->t('Default Level2'),
      '#default_value' => entity_load('level2', $config->get('default_level2')),
      '#description' => $this->t('Use this Level2 is none is set.'),
    );

    // ---

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();


    $this->config('atinternet.settings')
      ->set('default_level2', $values['default_level2'])
      ->set('smarttag_manual', $values['smarttag_manual'])
      ->set('smarttag_file', $values['smarttag_file'])
      ->set('smarttag_url', $values['smarttag_url'])
      ->set('visibility_request_path_mode', $values['visibility_request_path_mode'])
      ->set('visibility_request_path_pages', $values['visibility_request_path_pages'])
      ->set('visibility_user_role_mode', $values['visibility_user_role_mode'])
      ->set('visibility_user_role_roles', array_filter($values['visibility_user_role_roles']))
      ->save();
  }
}
