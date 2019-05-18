<?php

namespace Drupal\autoslave\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Autoslave Settings Form
 **/
class AutoslaveSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autoslave_settings_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!autoslave_is_driver_loaded()) {
      drupal_set_message(t('AutoSlave driver is not loaded'), 'warning');
    }
    $form['help'] = array(
      '#markup' => 'More help to come ...',
    );
    return $form;
  }
  
}