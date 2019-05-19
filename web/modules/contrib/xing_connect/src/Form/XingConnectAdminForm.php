<?php

namespace Drupal\xing_connect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure xing connect settings form for this site.
 */
class XingConnectAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xing_connect_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['xing_connect.admin.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('xing_connect.admin.settings');
    // Client Key of xing.
    $form['xing_connect_ckey'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Xing Consumer key'),
      '#default_value' => $config->get('xing_connect_ckey'),
      '#description' => $this->t('Also called the <em>OAuth Consumer key</em> value on Xing App settings pages. <a href="https://dev.xing.com/applications/dashboard">Xing Apps must first be created</a> before they can be added here.'),
    ];

    // Secret Key of xing.
    $form['xing_connect_skey'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Xing Consumer secret'),
      '#default_value' => $config->get('xing_connect_skey'),
      '#description' => $this->t('Also called the <em>OAuth Consumer secret</em> value on Xing App settings pages.'),
    ];

    // Enable Login with Xing.
    $form['xing_connect_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Login using xing'),
      '#description' => $this->t('Allow user to register and login with xing.'),
      '#default_value' => $config->get('xing_connect_login'),
    ];

    // Post Url.
    $form['xing_connect_post_login_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Post Login url'),
      '#description' => $this->t('Drupal URL to which the user should be redirected to after successful login.'),
      '#default_value' => $config->get('xing_connect_post_login_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('xing_connect.admin.settings')
      ->set('xing_connect_ckey', $form_state->getValue('xing_connect_ckey'))
      ->set('xing_connect_skey', $form_state->getValue('xing_connect_skey'))
      ->set('xing_connect_login', $form_state->getValue('xing_connect_login'))
      ->set('xing_connect_post_login_url', $form_state->getValue('xing_connect_post_login_url'))
      ->save();
    return parent::submitForm($form, $form_state);
  }

}
