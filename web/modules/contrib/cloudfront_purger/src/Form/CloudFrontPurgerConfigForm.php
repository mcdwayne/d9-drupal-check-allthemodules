<?php

namespace Drupal\cloudfront_purger\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\purge_ui\Form\PurgerConfigFormBase;

/**
 * Provides a config form for CloudFront purger.
 */
class CloudFrontPurgerConfigForm extends PurgerConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cloudfront_purger.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudfront_purger.config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['distribution_id'] = [
      '#type' => 'textfield',
      '#title' => t('Distribution ID'),
      '#default_value' => $this->config('cloudfront_purger.settings')
        ->get('distribution_id'),
    ];
    $form['aws_key'] = [
      '#type' => 'textfield',
      '#title' => t('AWS Key'),
      '#default_value' => $this->config('cloudfront_purger.settings')
        ->get('aws_key'),
    ];
    $form['aws_secret'] = [
      '#type' => 'textfield',
      '#title' => t('AWS Secret'),
      '#default_value' => $this->config('cloudfront_purger.settings')
        ->get('aws_secret'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormSuccess(array &$form, FormStateInterface $form_state) {
    $settings = $this->config('cloudfront_purger.settings');
    $settings->set('distribution_id', $form_state->getValue('distribution_id'));
    $settings->set('aws_key', $form_state->getValue('aws_key'));
    $settings->set('aws_secret', $form_state->getValue('aws_secret'));
    $settings->save();
  }

}
