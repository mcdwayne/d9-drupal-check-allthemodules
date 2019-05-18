<?php

namespace Drupal\issuu_viewer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'issuu_viewer_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'issuu_viewer.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('issuu_viewer.settings');

    $form['issuu_viewer_default_background'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Issuu viewer background color'),
      '#required' => TRUE,
      '#description' => $this->t('The default background color for Issuu viewer.'),
      '#default_value' => $config->get('issuu_viewer_default_background'),
    ];

    $form['issuu_viewer_default_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#size' => 10,
      '#required' => TRUE,
      '#description' => $this->t('The default height size for Issuu viewer.'),
      '#default_value' => $config->get('issuu_viewer_default_height'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('issuu_viewer.settings')
      ->set('issuu_viewer_default_background', $values['issuu_viewer_default_background'])
      ->set('issuu_viewer_default_height', $values['issuu_viewer_default_height'])
      ->save();
  }

}
