<?php

namespace Drupal\open_readspeaker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OpenReadSpeakerSettingsForm.
 *
 * @package Drupal\open_readspeaker\Form
 */
class OpenReadSpeakerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'open_readspeaker.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'open_read_speaker_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('open_readspeaker.settings');

    $language_options = array(
      'none'  => $this->t('Select a language'),
      'ar_ar' => $this->t('@arabic - Arabic', array('@arabic' => urldecode('%D8%B9%D8%B1%D8%A8%D9%8A'))),
      'ca_es' => $this->t('Català - Catalan'),
      'da_dk' => $this->t('Dansk - Danish'),
      'de_de' => $this->t('Deutsch - German'),
      'en_in' => $this->t('English (Indian) - English'),
      'en_us' => $this->t('English (US) - English'),
      'en_uk' => $this->t('English (British) - English'),
      'en_au' => $this->t('English (Australia) - English'),
      'es_co' => $this->t('Español (de Colombia) - Spanish'),
      'es_es' => $this->t('Español (de España) - Spanish'),
      'es_mx' => $this->t('Español (de Mexico) - Spanish'),
      'es_eu' => $this->t('Euskara - Basque'),
      'es_us' => $this->t('Español (de US) - Spanish'),
      'el_gr' => $this->t('Ελληνικά - Greek'),
      'fo_fo' => $this->t('Azerbaijani - Faroese'),
      'fi_fi' => $this->t('Suomi - Finnish'),
      'fr_fr' => $this->t('Français - French'),
      'fy_nl' => $this->t('Frysk - Frisian'),
      'gl_es' => $this->t('Galego - Galician'),
      'hi_in' => $this->t('@hindi - Hindi', array('@hindi' => urldecode('%E0%A4%B9%E0%A4%BF%E0%A4%A8%E0%A5%8D%E0%A4%A6%E0%A5%80%20(%E0%A4%AD%E0%A4%BE%E0%A4%B0%E0%A4%A4)'))),
      'it_it' => $this->t('Italiano - Italian'),
      'is_is' => $this->t('íslenska - Icelandic'),
      'ja_jp' => $this->t('日本語 - Japanese'),
      'ko_kr' => $this->t('한국어 - Korean'),
      'pt_pt' => $this->t('Português (Europeu) - Portuguese'),
      'nl_nl' => $this->t('Nederlands - Dutch'),
      'no_nb' => $this->t('Bokmål - Norwegian'),
      'no_nn' => $this->t('Nynorsk - Norwegian'),
      'ru_ru' => $this->t('Русский - Russian'),
      'sv_se' => $this->t('Svenska - Swedish'),
      'th_th' => $this->t('@thai - Thai', array('@thai' => urldecode('%E0%B9%84%E0%B8%97%E0%B8%A2'))),
      'tr_tr' => $this->t('Türkçe - Turkish'),
      'zh_cn' => $this->t('@mandarin - Mandarin Chinese', array('@mandarin' => urldecode('%E4%B8%AD%E6%96%87%20(%E7%AE%80%E4%BD%93)'))),
      'zh_hk' => $this->t('@traditional - Traditional Chinese', array('@traditional' => urldecode('%E4%B8%AD%E5%9C%8B%EF%BC%88%E7%B9%81%E9%AB%94%EF%BC%89'))),
    );

    $form['settings'] = [
      '#title' => $this->t('General settings for ReadSpeaker'),
      '#description' => $this->t('The ReadSpeaker module requires an own account'),
      '#type' => 'fieldset',
    ];

    $form['settings']['open_readspeaker_accountid'] = [
      '#title' => $this->t('Enter your ReadSpeaker ID'),
      '#description' => $this->t('Enter your ReadSpeaker ID from'),
      '#type' => 'textfield',
      '#default_value' => $config->get('open_readspeaker_accountid'),
      '#required' => TRUE,
    ];

    $form['settings']['open_readspeaker_i18n'] = array(
      '#title' => $this->t('Language'),
      '#description' => $this->t('Select which language your ReadSpeaker account supports.'),
      '#type' => 'select',
      '#options' => $language_options,
      '#default_value' => $config->get('open_readspeaker_i18n'),
      '#required' => TRUE,
    );

    $form['settings']['open_readspeaker_dev_mode'] = array(
      '#title' => $this->t('Enable development mode'),
      '#description' => $this->t('Enable dev mode so it can be testable non-public url site.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('open_readspeaker_dev_mode'),
    );
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
    parent::submitForm($form, $form_state);

    $this->config('open_readspeaker.settings')
      ->set('open_readspeaker_accountid', $form_state->getValue('open_readspeaker_accountid'))
      ->set('open_readspeaker_i18n', $form_state->getValue('open_readspeaker_i18n'))
      ->set('open_readspeaker_dev_mode', $form_state->getValue('open_readspeaker_dev_mode'))
      ->save();
  }

}
