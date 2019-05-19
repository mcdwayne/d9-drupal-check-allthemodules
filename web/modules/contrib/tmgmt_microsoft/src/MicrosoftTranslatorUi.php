<?php
/**
 * @file
 * Contains \Drupal\tmgmt_microsoft\MicrosoftTranslatorUi.
 */

namespace Drupal\tmgmt_microsoft;

use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Microsoft translator UI.
 */
class MicrosoftTranslatorUi extends TranslatorPluginUiBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    $register_app = 'https://datamarket.azure.com/developer/applications/';
    $form['client_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Client ID'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('client_id'),
      '#description' => t('Please enter the Client ID, or follow this <a href=":link">link</a> to set it up.', array(':link' => $register_app)),
    );
    $generate_url = 'https://datamarket.azure.com/developer/applications/edit/' . $translator->getSetting('client_id');
    $form['client_secret'] = array(
      '#type' => 'textfield',
      '#title' => t('Client secret'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('client_secret'),
      '#description' => t('Please enter the Client Secret, or follow this <a href=":link">link</a> to generate one.', array(':link' => $generate_url)),
    );
    $form['url'] = array(
      '#type' => 'hidden',
      '#default_value' => $translator->getSetting('url'),
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
    $supported_remote_languages = $translator->getPlugin()->getSupportedRemoteLanguages($translator);
    if (empty($supported_remote_languages)) {
      $form_state->setErrorByName('settings][client_id', t('The "Client ID", the "Client secret" or both are not correct.'));
      $form_state->setErrorByName('settings][client_secret', t('The "Client ID", the "Client secret" or both are not correct.'));
    }
  }

}
