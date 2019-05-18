<?php

namespace Drupal\pricespider\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The Price Spider Configuration Form.
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pricespider_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pricespider.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('pricespider.settings');

    $form['metatags'] = [
      '#type' => 'details',
      '#title' => $this->t('Product Spider Meta Tag values.'),
      '#open' => TRUE,
    ];

    $form['metatags']['ps_account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account Number'),
      '#description' => $this->t('Account Number (value placed in the ps-account metatag)'),
      '#default_value' => $config->get('ps.account'),
      '#required' => FALSE,
    ];

    $form['metatags']['ps_config'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Config ID'),
      '#description' => $this->t('Configuration ID (value placed in the ps-config metatag)'),
      '#default_value' => $config->get('ps.config'),
      '#required' => FALSE,
    ];

    $form['metatags']['ps_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#description' => $this->t('Key (value placed in the ps-key metatag). Used for the WTB button.'),
      '#default_value' => $config->get('ps.key'),
      '#required' => TRUE,
    ];

    $form['ps_js'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CDN Javascript File'),
      '#description' => $this->t('Path to Price Spider hosted Javascript file. (example: //cdn.pricespider.com/1/lib/ps-widget.js)'),
      '#default_value' => $config->get('ps.js'),
      '#required' => TRUE,
    ];

    $form['wtb'] = [
      '#type' => 'details',
      '#title' => $this->t('Where to Buy Page'),
      '#open' => TRUE,
    ];

    $form['wtb']['wtb_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Where To Buy page url'),
      '#description' => $this->t('Location of where the Where to Buy page should live. Leave off trailing slashes.'),
      '#default_value' => $config->get('wtb.uri'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('pricespider.settings')
      ->set('ps.account', $values['ps_account'])
      ->set('ps.config', $values['ps_config'])
      ->set('ps.key', $values['ps_key'])
      ->set('ps.js', $values['ps_js'])
      ->set('wtb.uri', filter_var($values['wtb_uri'], FILTER_SANITIZE_URL))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
