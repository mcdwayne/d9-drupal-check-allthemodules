<?php
/**
 * @file
 * Contains \Drupal\tmgmt_yandex_api\YandexTranslatorUi.
 */

namespace Drupal\tmgmt_yandex_api;

use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * yandex translator UI.
 */
class YandexTranslatorUi extends TranslatorPluginUiBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    $register_app = 'https://tech.yandex.com/translate/';
    $form['api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Yandex Appliction KEY'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('api_key'),
      '#description' => t('Please enter the API KEY, or follow this <a href=":link">link</a> to set it up.', array(':link' => $register_app)),
    );
    $form += parent::addConnectButton();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    $supported_remote_languages = $translator->getPlugin()
      ->getSupportedRemoteLanguages($translator);
    if (empty($supported_remote_languages)) {
      $form_state->setErrorByName('settings][api_key', t('Api test error, Probably The "API KEY" is not correct.'));
    }
  }

}
