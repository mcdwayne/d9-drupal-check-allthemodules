<?php

namespace Drupal\dat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DatSettingsForm.
 */
class DatSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dat.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dat_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dat.settings');
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Adminer title'),
      '#default_value' => $config->get('title'),
      '#required' => TRUE,
    ];
    $form['width'] = [
      '#type' => 'number',
      '#title' => t('Frame Width'),
      '#default_value' => $config->get('width'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $form['width_unit'] = [
      '#type' => 'textfield',
      '#title' => t('Frame Width Unit'),
      '#default_value' => $config->get('width_unit'),
      '#required' => TRUE,
      '#size' => 5,
    ];
    $form['height'] = [
      '#type' => 'number',
      '#title' => t('Frame Height'),
      '#default_value' => $config->get('height'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $form['height_unit'] = [
      '#type' => 'textfield',
      '#title' => t('Frame Height Unit'),
      '#default_value' => $config->get('height_unit'),
      '#required' => TRUE,
      '#size' => 5,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('dat.settings')
      ->set('title', $form_state->getValue('title'))
      ->set('width', $form_state->getValue('width'))
      ->set('width_unit', $form_state->getValue('width_unit'))
      ->set('height', $form_state->getValue('height'))
      ->set('height_unit', $form_state->getValue('height_unit'))
      ->save();
  }

}
