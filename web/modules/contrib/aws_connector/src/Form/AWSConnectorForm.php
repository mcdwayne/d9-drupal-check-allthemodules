<?php

namespace Drupal\aws_connector\Form;

use Drupal\aws_connector\Credentials\AWSCredentialProvider;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * AWS Settings form for aws_connector credential management.
 */
class AWSConnectorForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aws_connector_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    $config = $this->config('aws_connector.settings');
    $form['aws_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AWS ID'),
      '#default_value' => empty($config->get('aws_connector.aws_id')) ? NULL : $config->get('aws_connector.aws_id'),
      '#required' => TRUE,
    ];

    $form['aws_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AWS Secret'),
      '#default_value' => empty($config->get('aws_connector.aws_secret')) ? NULL : $config->get('aws_connector.aws_secret'),
      '#required' => TRUE,
    ];

    $form['aws_region'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AWS Region'),
      '#default_value' => empty($config->get('aws_connector.aws_region')) ? 'us-east-1' : $config->get('aws_connector.aws_region'),
      '#required' => TRUE,
    ];

    $form['aws_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AWS Endpoint'),
      '#default_value' => empty($config->get('aws_connector.aws_endpoint')) ? NULL : $config->get('aws_connector.aws_endpoint'),
      '#required' => TRUE,
    ];

    $form['aws_s3_bucket'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AWS S3 Bucket'),
      '#default_value' => empty($config->get('aws_connector.aws_s3_bucket')) ? NULL : $config->get('aws_connector.aws_s3_bucket'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('aws_connector.settings');
    $config->set('aws_connector.aws_id', $form_state->getValue('aws_id'));
    $config->set('aws_connector.aws_secret', $form_state->getValue('aws_secret'));
    $config->set('aws_connector.aws_region', $form_state->getValue('aws_region'));
    $config->set('aws_connector.aws_endpoint', $form_state->getValue('aws_endpoint'));
    $config->set('aws_connector.aws_s3_bucket', $form_state->getValue('aws_s3_bucket'));
    $config->save();

    // Need to clear Drupal's page cache so the changes can take effect.
    // @todo pass this data via an API call so we don't need to reset node cache.

    \Drupal::entityTypeManager()->getViewBuilder('node')->resetCache();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $error_message = AWSCredentialProvider::validateCredentials($form_state->getValue('aws_id'), $form_state->getValue('aws_secret'));
    if ($error_message != '') {
      $form_state->setErrorByName('aws_id', $error_message);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {

    return [
      'aws_connector.settings',
    ];
  }

}
