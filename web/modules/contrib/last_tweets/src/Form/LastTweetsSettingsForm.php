<?php

namespace Drupal\last_tweets\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LastTweetsSettingsForm.
 *
 * @package Drupal\last_tweets\Form
 */
class LastTweetsSettingsForm extends ConfigFormBase {

  protected $languageManager;

  /**
   * LastTweetsSettingsForm constructor.
   *
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   Language manager interface.
   */
  public function __construct(LanguageManager $language_manager) {
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
    return 'last_tweets_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'last_tweets.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $languages = $this->languageManager->getLanguages();
    $defaultLanguage = $this->languageManager->getDefaultLanguage()->getId();

    foreach ($languages as $language) {

      $name = $language->getName();
      $id = $language->getId();
      $weight = ($id == $defaultLanguage) ? -10 : 0;

      $form[$id] = [
        '#type' => 'fieldset',
        '#title' => 'Twitter ' . $name,
        '#weight' => $weight,
      ];
      $form[$id]['twitter_username_' . $id] = [
        '#type' => 'textfield',
        '#title' => $this->t('Twitter username'),
        '#maxlength' => 255,
        '#size' => 64,
        '#default_value' => $this->config('last_tweets.settings')->get('twitter_username_' . $id),
      ];
      $form[$id]['consumer_key_' . $id] = [
        '#type' => 'textfield',
        '#title' => $this->t('Consumer key'),
        '#maxlength' => 255,
        '#size' => 64,
        '#default_value' => $this->config('last_tweets.settings')->get('consumer_key_' . $id),
      ];
      $form[$id]['secret_key_' . $id] = [
        '#type' => 'textfield',
        '#title' => $this->t('Consumer secret'),
        '#maxlength' => 255,
        '#size' => 64,
        '#default_value' => $this->config('last_tweets.settings')->get('secret_key_' . $id),
      ];
      $form[$id]['access_token_' . $id] = [
        '#type' => 'textfield',
        '#title' => $this->t('Access token'),
        '#maxlength' => 255,
        '#size' => 64,
        '#default_value' => $this->config('last_tweets.settings')->get('access_token_' . $id),
      ];
      $form[$id]['access_token_secret_' . $id] = [
        '#type' => 'textfield',
        '#title' => $this->t('Access token secret'),
        '#maxlength' => 255,
        '#size' => 64,
        '#default_value' => $this->config('last_tweets.settings')->get('access_token_secret_' . $id),
      ];
      if ($id == $defaultLanguage) {
        $form[$id]['use_for_all'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Use this configuration for all languages'),
          '#default_value' => $this->config('last_tweets.settings')->get('use_for_all_' . $id),
        ];
      }
    }
    $form['#attached']['library'][] = 'last_tweets/admin-lib';
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $languages = $this->languageManager->getLanguages();
    $defaultLanguage = $this->languageManager->getDefaultLanguage()->getId();
    $config = $this->config('last_tweets.settings');

    foreach ($languages as $language) {
      $id = $language->getId();
      $config->set('secret_key_' . $id, $form_state->getValue('secret_key_' . $id));
      $config->set('consumer_key_' . $id, $form_state->getValue('consumer_key_' . $id));
      $config->set('twitter_username_' . $id, $form_state->getValue('twitter_username_' . $id));
      $config->set('access_token_' . $id, $form_state->getValue('access_token_' . $id));
      $config->set('access_token_secret_' . $id, $form_state->getValue('access_token_secret_' . $id));
    }
    $config->set('use_for_all_' . $defaultLanguage, $form_state->getValue('use_for_all'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
