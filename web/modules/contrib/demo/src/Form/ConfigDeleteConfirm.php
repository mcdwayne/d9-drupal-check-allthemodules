<?php

namespace Drupal\demo\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 *  confirms the deletion of config deletion
 */
class ConfigDeleteConfirm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_delete_confirm';
  }

  public $filename;

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $filename = NULL) {
    $fileconfig = demo_get_fileconfig($filename);
    $filename = $fileconfig['dumppath'] . '/' . $filename;

    if (!file_exists($filename)) {
      drupal_set_message(t('File not found'), 'error');
    }
    $form['filename'] = [
      '#type' => 'value',
      '#value' => $filename,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $filename = $form_state->getValue('filename');
    unlink($filename);
    drupal_set_message(t('Snapshot %title has been deleted.', [
      '%title' => $form_state->getValue(['filename']),
    ]));
    $form_state->setRedirect('demo.manage_config');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('demo.manage_config');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete this screenshot?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

}
