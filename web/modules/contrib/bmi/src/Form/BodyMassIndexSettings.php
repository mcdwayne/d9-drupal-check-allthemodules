<?php

/**
 * @file
 * Contains Drupal\bmi\Form\BodyMassIndexSettings.
 */

namespace Drupal\bmi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BodyMassIndexSettings.
 *
 * @package Drupal\bmi\Form
 */
class BodyMassIndexSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bmi.bodymassindexsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'body_mass_index_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bmi.bodymassindexsettings');
    $form['underweight_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Less than 18.5'),
      '#default_value' => $config->get('underweight_text'),
    );
    $form['normalweight_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('18.5 - 24.9'),
      '#default_value' => $config->get('normalweight_text'),
    );
    $form['obesity_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('BMI of 30 or greater'),
      '#default_value' => $config->get('obesity_text'),
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
    parent::submitForm($form, $form_state);

    $this->config('bmi.bodymassindexsettings')
      ->set('underweight_text', $form_state->getValue('underweight_text'))
      ->set('normalweight_text', $form_state->getValue('normalweight_text'))
      ->set('obesity_text', $form_state->getValue('obesity_text'))
      ->save();
  }

}
