<?php

namespace Drupal\block_upload\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Module configuration form.
 */
class BlockUploadSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_upload_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['block_upload.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['block_upload_blocks_count'] = [
      '#type' => 'select',
      '#title' => $this->t('Blocks count'),
      '#options' => array_combine(range(1, 10), range(1, 10)),
      '#default_value' => $this->config('block_upload.settings')->get('blocks_count'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $blocks_count = $form_state->getValue('block_upload_blocks_count');
    $this->config('block_upload.settings')
      ->set('blocks_count', $blocks_count)
      ->save();
    \Drupal::state()->set('block_upload_count', $blocks_count);
    parent::submitForm($form, $form_state);
    drupal_flush_all_caches();
  }

}
