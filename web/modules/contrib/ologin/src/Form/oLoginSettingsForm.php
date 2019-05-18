<?php

namespace Drupal\ologin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures module settings.
 */
class oLoginSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ologin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'ologin.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {

    $config = $this->config('ologin.settings');

    // Weixin QR Connect
    $form['ologin_weixin'] = array(
      '#type'  => 'fieldset',
      '#title' => $this->t('Wechat login'),
      '#collapsible' => TRUE, 
    );

    $form['ologin_weixin']['ologin_weixin_callback'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Wechat Callback URL'),
      '#default_value' => $config->get('ologin_weixin_callback'),
      '#required' => TRUE,
    );

    $form['ologin_weixin']['ologin_weixin_appkey'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('AppKey'),
      '#default_value' => $config->get('ologin_weixin_appkey'),
      '#required' => TRUE,
    );

    $form['ologin_weixin']['ologin_weixin_appsecret'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('AppSecret'),
      '#default_value' => $config->get('ologin_weixin_appsecret'),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('ologin.settings')
      ->set('ologin_weixin_callback', $values['ologin_weixin_callback'])
      ->set('ologin_weixin_appkey', $values['ologin_weixin_appkey'])
      ->set('ologin_weixin_appsecret', $values['ologin_weixin_appsecret'])
      ->save();
  }

}