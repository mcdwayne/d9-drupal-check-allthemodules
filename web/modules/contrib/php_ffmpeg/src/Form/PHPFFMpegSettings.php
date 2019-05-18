<?php

namespace Drupal\php_ffmpeg\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Config form for the PHP FFMPeg module's options.
 */
class PHPFFMpegSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'php_ffmpeg_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['php_ffmpeg.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['ffmpeg_binary'] = [
      '#type' => 'textfield',
      '#title' => t('ffmpeg binary'),
      '#description' => t('Path to the ffmpeg binary. Leave empty if the binary is located within system PATH.'),
      '#default_value' => $this->config('php_ffmpeg.settings')->get('ffmpeg_binary'),
    ];
    $form['ffprobe_binary'] = [
      '#type' => 'textfield',
      '#title' => t('ffprobe binary'),
      '#description' => t('Path to the ffprobe binary. Leave empty if the binary is located within system PATH.'),
      '#default_value' => $this->config('php_ffmpeg.settings')->get('ffprobe_binary'),
    ];
    $form['execution_timeout'] = [
      '#type' => 'number',
      '#title' => t('Timeout'),
      '#description' => t('Timeout for ffmpeg/ffprobe command execution in seconds. Leave empty for none.'),
      '#default_value' => $this->config('php_ffmpeg.settings')->get('execution_timeout'),
      '#min' => 0,
      '#step' => 1,
    ];
    $form['threads_amount'] = [
      '#type' => 'number',
      '#title' => t('Threads'),
      '#description' => t('Number of threads to use for ffmpeg commands.'),
      '#default_value' => $this->config('php_ffmpeg.settings')->get('threads_amount'),
      '#min' => 0,
      '#step' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['ffmpeg_binary']) && !file_exists($form_state->getValue(['ffmpeg_binary']))) {
      $form_state->setErrorByName(
        'ffmpeg_binary',
        t(
          'File not found: @file',
          ['@file' => $form_state->getValue(['ffmpeg_binary'])]
        )
      );
    }
    if ($form_state->getValue(['ffprobe_binary']) && !file_exists($form_state->getValue(['ffprobe_binary']))) {
      $form_state->setErrorByName(
        'ffprobe_binary',
        t(
          'File not found: @file',
          ['@file' => $form_state->getValue(['ffprobe_binary'])]
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('php_ffmpeg.settings')
      ->set('ffmpeg_binary', $form_state->getValue('ffmpeg_binary'))
      ->set('ffprobe_binary', $form_state->getValue('ffprobe_binary'))
      ->set('execution_timeout', $form_state->getValue('execution_timeout'))
      ->set('threads_amount', $form_state->getValue('threads_amount'))
      ->save();
  }

}
