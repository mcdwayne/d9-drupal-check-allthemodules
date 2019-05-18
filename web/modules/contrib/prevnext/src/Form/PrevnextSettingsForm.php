<?php

/**
 * @file
 * Contains \Drupal\prevnext\Form\PrevnextSettingsForm.
 */

namespace Drupal\prevnext\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PrevnextSettingsForm.
 *
 * @package Drupal\prevnext\Form
 */
class PrevnextSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'prevnext_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['prevnext.settings'];
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('prevnext.settings');

    $form['prevnext_enabled_nodetypes'] = array(
      '#title' => $this->t('Enabled Node Types'),
      '#description' => $this->t('Check node types enabled for Previous/Next'),
      '#type' => 'checkboxes',
      '#options' => node_type_get_names(),
      '#default_value' => $config->get('prevnext_enabled_nodetypes'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the config values.
    $this->config('prevnext.settings')
      ->set('prevnext_enabled_nodetypes', $form_state->getValue('prevnext_enabled_nodetypes'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
