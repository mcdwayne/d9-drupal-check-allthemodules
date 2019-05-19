<?php

namespace Drupal\synapse\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/**
 * Implements the form controller.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'synapse_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['synapse.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('synapse.settings');
    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $gtm = Markup::create("<a target='_blank' href='https://tagmanager.google.com/?hl=$lang'>Google Tag Manager</a>");
    $google = Markup::create("<a target='_blank' href='https://www.google.com/webmasters/tools/home?hl=$lang'>Google webmaster</a>");
    $gmeta = "&lt;meta name='google-site-verification' content='<strong>__THIS_CODE__</strong>'&gt;";
    $yandex = Markup::create("<a target='_blank' href='https://webmaster.yandex.ru/site/add.xml'>Yandex webmaster</a>");
    $ymeta = "&lt;meta name='yandex-verification' content='<strong>__THIS_CODE__</strong>'&gt;";

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form['general']['gtm_id'] = [
      '#title' => $this->t('GTM-ID'),
      '#default_value' => $config->get('gtm-id'),
      '#maxlength' => 20,
      '#size' => 15,
      '#type' => 'textfield',
      '#description' => $this->t("You can add site to @href", ['@href' => $gtm]),
    ];
    $form['general']['gtm_admin_disable'] = [
      '#title' => $this->t('Disable GTM for user 1'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('gtm-admin-disable'),
    ];

    $form['general']['webmaster_yandex'] = [
      '#title' => $this->t('Yandex Webmaster'),
      '#default_value' => $config->get('wm-yandex'),
      '#maxlength' => 255,
      '#size' => 80,
      '#type' => 'textfield',
      '#description' => "$ymeta<br>" . $this->t("You can add site to @href", ['@href' => $yandex]),
    ];
    $form['general']['webmaster_google'] = [
      '#title' => $this->t('Google Webmaster'),
      '#default_value' => $config->get('wm-google'),
      '#maxlength' => 255,
      '#size' => 80,
      '#type' => 'textfield',
      '#description' => "$gmeta<br>" . $this->t("You can add site to @href", ['@href' => $google]),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('synapse.settings');
    $config
      ->set('gtm-id', $form_state->getValue('gtm_id'))
      ->set('gtm-admin-disable', $form_state->getValue('gtm_admin_disable'))
      ->set('wm-yandex', $form_state->getValue('webmaster_yandex'))
      ->set('wm-google', $form_state->getValue('webmaster_google'))
      ->save();
  }

}
