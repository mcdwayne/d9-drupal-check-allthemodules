<?php

namespace Drupal\rakuten\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures rakuten settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rakuten_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['rakuten.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('rakuten.settings');

    $form['rakuten_on'] = [
      '#type' => 'checkbox',
      '#title' => t('Activate Rakuten Web Service SDK for PHP.'),
      '#default_value' => $config->get('rakuten_on'),
      '#description' => t('First create your App in https://webservice.rakuten.co.jp/app/list'),
    ];

    if (!$config->get('rakuten_on') && empty($form_state->input)) {
      drupal_set_message(t('Rakuten is currently disabled.'), 'warning');
    }
    else {
      $form['rakuten_app_id'] = [
        '#type' => 'textfield',
        '#title' => t('Application ID/developer ID'),
        '#default_value' => $config->get('rakuten_app_id'),
        '#description' => t('Please enter your Application ID, eg: 1051011824618162114.'),
      ];

      $form['rakuten_app_secret'] = [
        '#type' => 'textfield',
        '#title' => t('Application Secret'),
        '#default_value' => $config->get('rakuten_app_secret'),
        '#description' => t('Please enter your Application Secret, eg: 1ed3175ffo8eb21d64a9b2eea50f40f9e030d3c5'),
      ];

      $form['rakuten_affiliate_id'] = [
        '#type' => 'textfield',
        '#title' => t('Affiliate ID'),
        '#default_value' => $config->get('rakuten_affiliate_id'),
        '#description' => t('Please enter your Affiliate ID, eg: 1968c401.62e6z17b.1690c402.b1c626f9'),
      ];

      $form['rakuten_domains'] = [
        '#type' => 'textfield',
        '#title' => t('Domain allowed for callbacks'),
        '#default_value' => $config->get('rakuten_domains'),
        '#description' => t('Please enter your callback domains, eg: raku.spinetta.tech, raku2.spinetta.tech'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Check to see if the module has been activated or inactivated.
    if ($values['rakuten_on']) {
      if (!rakuten_active()) {
        drupal_set_message(t('Rakuten Web Service SDK for PHP is ready to use in your modules.'));
        \Drupal::logger('rakuten')->notice('rakuten has been enabled.');
      }
    }
    elseif (rakuten_active()) {
      // This module is active and is being inactivated.
      drupal_set_message(t('Rakuten has been disabled.'));
      \Drupal::logger('rakuten')->notice('rakuten has been disabled.');
    }

    // Save the configuration changes.
    $rakuten_config = $this->config('rakuten.settings');
    $rakuten_config->set('rakuten_on', $values['rakuten_on']);

    if (rakuten_active()) {
      $rakuten_config->set('rakuten_app_id', $values['rakuten_app_id']);
      $rakuten_config->set('rakuten_app_secret', $values['rakuten_app_secret']);
      $rakuten_config->set('rakuten_affiliate_id', $values['rakuten_affiliate_id']);
      $rakuten_config->set('rakuten_domains', $values['rakuten_domains']);
    }

    $rakuten_config->save();

    parent::submitForm($form, $form_state);
  }
}
