<?php

namespace Drupal\pet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PetSettingsForm.
 * @package Drupal\pet\Form
 * @ingroup pet
 */
class PetSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pet_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('pet.settings')
      ->set('pet_logging', $form_state->getValue('pet_logging'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $pet_logging = \Drupal::config('pet.settings')->get('pet_logging');

    $form['logging'] = array(
      '#type' => 'details',
      '#title' => t('PET log settings'),
      '#open' => TRUE,
    );

    $options = array(
      0 => t('Log everything.'),
      1 => t('Log errors only.'),
      2 => t('No logging, display error on screen, useful for debugging.'),
    );

    $form['logging']['pet_logging'] = array(
      '#type' => 'radios',
      '#title' => t('Log setting'),
      '#options' => $options,
      '#default_value' => $pet_logging,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );
    return $form;
  }
}
