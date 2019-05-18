<?php

/**
 * @file
 * This file contains the code for Admin settings form.
 */

namespace Drupal\pack_upload\Form;

use Drupal\Core\Form\ConfigFormBase;

class Admin extends ConfigFormBase {

  /**
   * {@inheritdoc}
   * @see \Drupal\Core\Form\FormInterface::getFormId()
   */
  public function getFormId() {
    return 'pack_upload_admin_settings';
  }

  /**
   * {@inheritdoc}
   * @see \Drupal\Core\Form\ConfigFormBase::buildForm()
   */
  public function buildForm(array $form, array &$form_state) {
    $form['panel'] = array(
      '#title' => t('Bulk Media Uploader'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
    );
    $config = $this->config('pack_upload.settings');

    $form['panel']['path'] = array(
      '#title' => t('Bulk Media Extraction Directory'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#description' => t('Name of the directory where you want all files to be extracted.'),
      '#default_value' => $config->get('path'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   * @see \Drupal\Core\Form\ConfigFormBase::submitForm()
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->config('pack_upload.settings')
        ->set('path', $form_state['values']['path'])
        ->save();

    parent::submitForm($form, $form_state);
  }
}
