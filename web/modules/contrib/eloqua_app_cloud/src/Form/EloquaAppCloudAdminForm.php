<?php

namespace Drupal\eloqua_app_cloud\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\CachedStorage;

/**
 * Class EloquaAppCloudAdminForm.
 *
 * @package Drupal\eloqua_app_cloud\Form
 */
class EloquaAppCloudAdminForm extends ConfigFormBase {

  protected $settingKeys = [
    'oauth_client_id',
    'oauth_client_secret',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'eloqua_app_cloud.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eloqua_app_cloud_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('eloqua_app_cloud.settings');

    // OAuth Client ID
    $form['oauth_client_id'] = [
      '#title' => $this->t('App OAuth Client ID'),
      '#description' => $this->t('The OAuth Client ID for your application as assigned to you by Eloqua.'),
      '#type' => 'textfield',
      '#default_value' => $config->get('oauth_client_id'),
      '#required' => TRUE,
    ];

    // OAuth Client Secret
    $form['oauth_client_secret'] = [
      '#title' => $this->t('App OAuth Client Secret'),
      '#description' => $this->t('The OAuth Client Secret for your application as assigned to you by Eloqua.'),
      '#type' => 'textfield',
      '#default_value' => $config->get('oauth_client_secret'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
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
    $config = $this->configFactory->getEditable('eloqua_app_cloud.settings');
    $formData = $form_state->getValues();

    // Filter out all of the normal form state noise and just return our settings.
    foreach ($formData as $key => $value) {
      if (!in_array($key, $this->settingKeys)) {
        unset($formData[$key]);
      }
    }

    // Store the configs!
    $config->setData($formData);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
