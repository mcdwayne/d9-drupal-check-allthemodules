<?php

/**
 * @file
 * Contains \Drupal\whiteboard\Form\WhiteboardSettingsForm.
 */

namespace Drupal\whiteboard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Presents the module settings form.
 */
class WhiteboardSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'whiteboard_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['whiteboard.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('whiteboard.settings');

    $form['size'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Maximum Whiteboard size'),
      '#default_value' => $config->get('size'),
      '#description' => $this->t("Enter the maximum number of marks that a whiteboard may save to the database."),
    );
    
    return parent::buildForm($form, $form_state);
  }  

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('whiteboard.settings')
        ->set('size', $form_state->getValue('size'))
        ->save();

    parent::submitForm($form, $form_state);
  }
}
