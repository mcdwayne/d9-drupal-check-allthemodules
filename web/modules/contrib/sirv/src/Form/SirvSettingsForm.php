<?php

namespace Drupal\sirv\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form to enter Sirv settings.
 */
class SirvSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sirv_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sirv.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('sirv.settings');

    $form['access_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Access key'),
      '#default_value' => $config->get('access_key'),
    ];
    $form['secret_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Secret key'),
      '#default_value' => $config->get('secret_key'),
    ];
    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#default_value' => $config->get('endpoint'),
    ];
    $form['bucket'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bucket'),
      '#default_value' => $config->get('bucket'),
    ];
    $form['root_dir'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Root directory'),
      '#default_value' => $config->get('root_dir'),
    ];
    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#default_value' => $config->get('domain'),
      '#field_prefix' => 'https://',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('sirv.settings')
      ->set('access_key', $values['access_key'])
      ->set('secret_key', $values['secret_key'])
      ->set('endpoint', $values['endpoint'])
      ->set('bucket', $values['bucket'])
      ->set('root_dir', $values['root_dir'])
      ->set('domain', $values['domain'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
