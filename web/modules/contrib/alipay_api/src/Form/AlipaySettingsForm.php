<?php

namespace Drupal\alipay_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures module settings.
 */
class AlipaySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'alipay_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'alipay.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {

    $config = $this->config('alipay.settings');

    // Weixin QR Connect.
    $form['alipay'] = array(
      '#type'  => 'fieldset',
      '#title' => $this->t('Alipay - Direct Pay'),
      '#collapsible' => TRUE,
    );

    $form['alipay']['alipay_partner'] = array(
      '#type' => 'textfield',
      '#title' => 'Partner ID',
      '#maxlength' => 16,
      '#default_value' => $config->get('alipay_partner'),
      '#required' => TRUE,
    );

    $form['alipay']['alipay_md5_key'] = array(
      '#type' => 'textfield',
      '#title' => 'MD5 Key',
      '#maxlength' => 32,
      '#default_value' => $config->get('alipay_md5_key'),
      '#required' => TRUE,
    );

    $form['alipay']['alipay_notify_url'] = array(
      '#type' => 'textfield',
      '#title' => 'Notify URL',
      '#disabled' => TRUE,
      '#value' => \Drupal::url('alipay.notify', array(), array('absolute' => TRUE)),
    );

    $form['alipay']['alipay_return_url'] = array(
      '#type' => 'textfield',
      '#title' => 'Return URL',
      '#disabled' => TRUE,
      '#value' => \Drupal::url('alipay.return', array(), array('absolute' => TRUE)),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('alipay.settings')
      ->set('alipay_partner', $values['alipay_partner'])
      ->set('alipay_md5_key', $values['alipay_md5_key'])
      ->save();
  }

}
