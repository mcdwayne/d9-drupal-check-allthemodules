<?php

namespace Drupal\social_auth_wechat\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\social_auth\Form\SocialAuthSettingsForm;

/**
 * Settings form for Social Auth WeChat.
 */
class WeChatAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array_merge(array('social_auth_wechat.settings'), parent::getEditableConfigNames());
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_wechat_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_wechat.settings');

    $form['wechat_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('WeChat Client settings'),
      '#open' => TRUE,
    );

    $form['wechat_settings']['client_id'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('Copy the Client ID here'),
    );

    $form['wechat_settings']['client_secret'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('Copy the Client Secret here'),
    );

    $form['wechat_settings']['client_scope'] = array(
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Client Scope'),
      '#default_value' => $config->get('client_scope'),
      '#description' => $this->t('To see the differences between scopes, please see <a href="@wechat-oauth-doc" target="_blank">the documentation</a>', ['@wechat-oauth-doc' => 'https://easywechat.org/zh-cn/docs/oauth.html#微信-OAuth']),
      '#options' => [
        'snsapi_base' => 'snsapi_base',
        'snsapi_userinfo' => 'snsapi_userinfo',
        'snsapi_login' => 'snsapi_login',
      ],
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('social_auth_wechat.settings')
      ->set('client_id', $values['client_id'])
      ->set('client_secret', $values['client_secret'])
      ->set('client_scope', $values['client_scope'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
