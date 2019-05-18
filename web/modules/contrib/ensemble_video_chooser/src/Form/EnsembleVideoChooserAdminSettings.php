<?php

namespace Drupal\ensemble_video_chooser\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Implementation of the Ensemble Video Chooser configuration form.
 */
class EnsembleVideoChooserAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ensemble_video_chooser_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ensemble_video_chooser.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ensemble_video_chooser.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['ensemble_video_chooser_launch_url'] = [
      '#type' => 'textfield',
      '#title' => t('Launch Url'),
      '#default_value' => \Drupal::config('ensemble_video_chooser.settings')->get('ensemble_video_chooser_launch_url'),
      '#description' => t('LTI Launch Url.'),
      '#required' => TRUE,
    ];
    $form['ensemble_video_chooser_consumer_key'] = [
      '#type' => 'textfield',
      '#title' => t('Consumer Key'),
      '#default_value' => \Drupal::config('ensemble_video_chooser.settings')->get('ensemble_video_chooser_consumer_key'),
      '#description' => t('LTI Consumer Key.'),
      '#required' => TRUE,
    ];
    $form['ensemble_video_chooser_shared_secret'] = [
      '#type' => 'password',
      '#title' => t('Shared Secret'),
      '#default_value' => \Drupal::config('ensemble_video_chooser.settings')->get('ensemble_video_chooser_shared_secret'),
      '#description' => t('LTI Shared Secret.'),
      '#required' => TRUE,
    ];
    $form['ensemble_video_chooser_custom_params'] = [
      '#type' => 'textarea',
      '#title' => t('Additional Parameters'),
      '#default_value' => \Drupal::config('ensemble_video_chooser.settings')->get('ensemble_video_chooser_custom_params'),
      '#description' => t('Additional LTI Parameters.'),
      '#required' => FALSE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $param_text = $form_state->getValue(['ensemble_video_chooser_custom_params']);
    $params = explode("\r\n", $param_text);
    $parsed_params = [];
    foreach ($params as $param) {
      $param = trim($param);
      if ($param === '') {
        continue;
      }
      if (!preg_match('/^custom_/', $param)) {
        $form_state->setErrorByName('ensemble_video_chooser_custom_params', t('"@param" must start with "custom_".', [
          '@param' => $param,
        ]));
        return;
      }
      $parts = explode('=', $param);
      if (count($parts) !== 2) {
        $form_state->setErrorByName('ensemble_video_chooser_custom_params', t('"@param" should be in the format "key=value".', [
          '@param' => $param,
        ]));
        return;
      }
      $parts[0] = trim($parts[0]);
      $parts[1] = trim($parts[1]);
      $param = implode('=', $parts);
      $parsed_params[] = $param;
    }
    $form_state->setValue(['ensemble_video_chooser_custom_params'], implode("\n", $parsed_params));
  }

}
