<?php

namespace Drupal\yandexdisk\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Yandex.Disk module.
 */
class YandexDiskAdmin extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yandexdisk_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['yandexdisk.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['buffer_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Buffer size'),
      '#description' => $this->t('The size of internal buffer for file reading operations (in bytes). Default value is 1048576 bytes which is 1 MB.'),
      '#default_value' => $this->config('yandexdisk.settings')->get('buffer_size'),
      '#field_suffix' => $this->t('bytes'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('yandexdisk.settings')
      ->set('buffer_size', $form_state->getValue('buffer_size'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
