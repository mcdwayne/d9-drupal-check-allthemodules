<?php

namespace Drupal\itsyouonline\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure itsyouonline account for this site.
 */
class UserFieldsSettingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'itsyouonline_admin_settings_user_fields';
  }

  protected function getEditableConfigNames() {
    return ['itsyouonline.fields'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    $config = $this->config('itsyouonline.fields');

    $display_params = _itsyouonline_display_params();

    $display_params_selected = $config->get('user_display_fields');

    $form['items'] = array();
    $form['items']['#tree'] = TRUE;

    foreach ($display_params as $key => $title) {
      $form['items'][$key] = array(
        'selected' => array(
          '#type' => 'checkbox',
          '#title' => $title,
          '#default_value' => isset($display_params_selected[$key]) ? $display_params_selected[$key]['selected']: FALSE,
        ),
        'title' => array(
          '#type' => 'textfield',
          '#default_value' => isset($display_params_selected[$key]) ? $display_params_selected[$key]['title']: '',
        ),
        'weight' => array(
          '#type' => 'weight',
          '#delta' => count($display_params),
          '#default_value' => isset($display_params_selected[$key]) ? $display_params_selected[$key]['weight']: 0,
        ),
        'name' => array(
          '#type' => 'hidden',
          '#value' => $key,
        ),
      );
    }

    $form['data_scope'] = array(
      '#type' => 'fieldset',
      '#title' => t('User data scopes'),
    );

    $form['data_scope']['user_data_scopes'] = array(
      '#type' => 'checkboxes',
      '#options' => _itsyouonline_scope_params(),
      '#default_value' => $config->get('user_scope_fields'),
      '#title' => t('User attributes to retrieve from itsyou.online'),
      '#description' => t("Select the user attributes that you would like to retrieve from itsyou.online. Email and name are required. If you only want to authenticate users, then you don't have to select any other attributes."),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $user_data_scopes = $form_state->getValue('user_data_scopes');

    if (empty($user_data_scopes['email']) ||
      empty($user_data_scopes['name'])) {
      $form_state->setErrorByName('user_data_scopes[email]', t('Email and name are required for scope field'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('itsyouonline.fields');
    $config
      ->set('user_display_fields', $form_state->getValue('items'))
      ->set('user_scope_fields', $form_state->getValue('user_data_scopes'))
      ->save();

    // @note: clearning user view cache.
    \Drupal::entityManager()->getViewBuilder('user')->resetCache();

    parent::submitForm($form, $form_state);
  }

}
