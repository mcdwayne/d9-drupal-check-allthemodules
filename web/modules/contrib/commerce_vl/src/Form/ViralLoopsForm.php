<?php

namespace Drupal\commerce_vl\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ViralLoopsForm.
 */
class ViralLoopsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_vl.viralloops',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dcom_viral_loops_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_vl.viralloops');
    $form['vl_campaign_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Campaign ID'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('vl_campaign_id'),
    ];
    $form['vl_api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API token'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('vl_api_token'),
    ];
    $form['vl_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Visibility delay'),
      '#min' => 0,
      '#step' => 0.1,
      '#field_suffix' => $this->t('sec'),
      '#default_value' => $config->get('vl_delay') ?: 0,
      '#description' => $this->t('Set a Viral Loops block visibility delay after page loading.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('commerce_vl.viralloops')
      ->set('vl_campaign_id', $form_state->getValue('vl_campaign_id'))
      ->set('vl_api_token', $form_state->getValue('vl_api_token'))
      ->set('vl_delay', $form_state->getValue('vl_delay'))
      ->save();
  }

}
