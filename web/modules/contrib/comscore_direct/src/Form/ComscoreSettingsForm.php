<?php

namespace Drupal\comscore_direct\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a configuration form for comscore settings.
 */
class ComscoreSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['comscore_direct.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'comscore_direct_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('comscore_direct.settings');

    $form['site_id'] = [
      '#title' => $this->t('comScore site id'),
      '#type' => 'textfield',
      '#description' => $this->t('comScore ID (required)'),
      '#default_value' => $config->get('site_id'),
      '#required' => TRUE,
    ];

    $form['genre'] = [
      '#title' => $this->t('comScore Genre of content'),
      '#type' => 'textfield',
      '#description' => $this->t('Alphanumeric value used for client specific custom classification (optional)'),
      '#default_value' => $config->get('genre'),
    ];

    $form['package'] = [
      '#title' => $this->t('comScore Package'),
      '#type' => 'textfield',
      '#description' => $this->t('Alphanumeric value for customized aggregation to reflect sections or site centric advertising packages (optional)'),
      '#default_value' => $config->get('package'),
    ];

    $form['clientseg'] = [
      '#title' => $this->t('comScore Client Segment ID'),
      '#type' => 'textfield',
      '#description' => $this->t('Alphanumeric value for Publisher’s own segment for the machine the content asset is being served to (optional)'),
      '#default_value' => $config->get('clientseg'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (!is_numeric($form_state->getValue('site_id'))) {
      $form_state->setErrorByName('site_id', $this->t('You must enter an integer for comScore customer code.'));
    }
    if ($form_state->getValue('site_id') < 0) {
      $form_state->setErrorByName('site_id', $this->t('comScore customer code must be positive.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('comscore_direct.settings')
      ->set('site_id', $form_state->getValue('site_id'))
      ->set('genre', $form_state->getValue('genre'))
      ->set('package', $form_state->getValue('package'))
      ->set('clientseg', $form_state->getValue('clientseg'))
      ->save();
  }

}
