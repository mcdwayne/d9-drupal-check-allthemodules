<?php

namespace Drupal\twitter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure twitter settings for this site.
 */
class TwitterSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twitter_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['twitter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $twitter_config = $this->configFactory->get('twitter.settings');
    $form['import'] = [
      '#type' => 'checkbox',
      '#title' => t('Import and display the Twitter statuses of site users who have entered their Twitter account information.'),
      '#default_value' => $twitter_config->get('import'),
    ];
    $intervals = [604800, 2592000, 7776000, 31536000];
    $form['expire'] = [
      '#type' => 'select',
      '#title' => t('Delete old statuses'),
      '#default_value' => $twitter_config->get('expire'),
      '#options' => [0 => t('Never')] + array_map([\Drupal::service('date.formatter'), 'formatInterval'], array_combine($intervals, $intervals)),
      '#states' => [
        'visible' => [
          ':input[name=twitter_import]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['oauth'] = [
      '#type' => 'fieldset',
      '#title' => t('OAuth Settings'),
      '#description' => t('To enable OAuth based access for twitter, you must <a href="@url">register your application</a> with Twitter and add the provided keys here.', ['@url' => 'https://dev.twitter.com/apps/new']),
    ];
    $form['oauth']['callback_url'] = [
      '#type' => 'item',
      '#title' => t('Callback URL'),
      '#markup' => Url::fromUri('base:twitter/oauth', ['absolute' => TRUE])->toString(),
    ];
    $form['oauth']['consumer_key'] = [
      '#type' => 'textfield',
      '#title' => t('OAuth Consumer key'),
      '#default_value' => $twitter_config->get('consumer_key'),
    ];
    $form['oauth']['consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => t('OAuth Consumer secret'),
      '#default_value' => $twitter_config->get('consumer_secret'),
    ];
    // Twitter external APIs settings.
    $form['twitter'] = [
      '#type' => 'fieldset',
      '#title' => t('Twitter Settings'),
      '#description' => t("The following settings connect Twitter module with external APIs. ' .
        'Change them if, for example, you want to use Identi.ca."),
    ];
    $form['twitter']['host'] = [
      '#type' => 'textfield',
      '#title' => t('Twitter host'),
      '#default_value' => $twitter_config->get('host'),
    ];
    $form['twitter']['api'] = [
      '#type' => 'textfield',
      '#title' => t('Twitter API'),
      '#default_value' => $twitter_config->get('api'),
    ];
    $form['twitter']['search'] = [
      '#type' => 'textfield',
      '#title' => t('Twitter search'),
      '#default_value' => $twitter_config->get('search'),
    ];
    $form['twitter']['tinyurl'] = [
      '#type' => 'textfield',
      '#title' => t('TinyURL'),
      '#default_value' => $twitter_config->get('tinyurl'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $twitter_config = $this->configFactory->getEditable('twitter.settings');
    $twitter_config
      ->set('import', $form_state->getValue('import'))
      ->set('expire', $form_state->getValue('expire'))
      ->set('consumer_key', $form_state->getValue('consumer_key'))
      ->set('consumer_secret', $form_state->getValue('consumer_secret'))
      ->set('host', $form_state->getValue('host'))
      ->set('api', $form_state->getValue('api'))
      ->set('search', $form_state->getValue('search'))
      ->set('tinyurl', $form_state->getValue('tinyurl'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
