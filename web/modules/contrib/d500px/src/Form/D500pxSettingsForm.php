<?php

namespace Drupal\d500px\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Implements the 500px Settings form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class D500pxSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'd500px_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'd500px.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('d500px.settings');

    $form['oauth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('OAuth Settings'),
    ];

    $form['oauth']['help'] = [
      '#type' => '#markup',
      '#markup' => $this->t('To get your OAuth credentials, you need to register your application on @link.', ['@link' => Link::fromTextAndUrl('https://500px.com/settings/applications', Url::fromUri('https://500px.com/settings/applications'))->toString()]),
    ];

    $form['oauth']['oauth_consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OAuth Consumer key'),
      '#default_value' => $config->get('oauth_consumer_key'),
    ];

    $form['oauth']['oauth_consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OAuth Consumer secret'),
      '#default_value' => $config->get('oauth_consumer_secret'),
    ];

    $form['d500px'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('500px Settings'),
      '#description' => $this->t('The following settings connect 500px module with external APIs.'),
    ];

    $form['d500px']['host_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('500px Host'),
      '#default_value' => $config->get('host_uri'),
    ];

    $form['d500px']['api_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('500px API'),
      '#default_value' => $config->get('api_uri'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('d500px.settings')
      ->set('oauth_consumer_key', $form_state->getValue('oauth_consumer_key'))
      ->set('oauth_consumer_secret', $form_state->getValue('oauth_consumer_secret'))
      ->set('host_uri', $form_state->getValue('host_uri'))
      ->set('api_uri', $form_state->getValue('api_uri'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
