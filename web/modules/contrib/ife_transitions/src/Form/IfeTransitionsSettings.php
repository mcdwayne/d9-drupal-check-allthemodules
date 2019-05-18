<?php

namespace Drupal\ife_transitions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays the ife transitions settings form.
 */
class IfeTransitionsSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ife_transitions_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['ife_transitions.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load IFE_Transitions Config.
    $config = $this->config('ife_transitions.settings');

    // Duration Element.
    $form['ife_transitions_time'] = [
      '#type' => 'number',
      '#default_value' => $config->get('ife_transitions_time'),
      '#title' => $this->t('Transition Duration'),
      '#description' => $this->t('Duration(time in Milliseconds) to scroll to error hyperlink.'),
    ];

    // Back To Top Controller.
    $form['ife_transitions_back_to_top'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('ife_transitions_back_to_top'),
      '#title' => $this->t('Enable Back To Top'),
      '#description' => $this->t('Enables Back To Top button when there are errors on page.'),
    ];

    // Back To Top Text.
    $form['ife_transitions_back_to_top_text'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('ife_transitions_back_to_top_text'),
      '#title' => $this->t('Back To Top Text'),
      '#states' => [
        'visible' => [
          'input[name="ife_transitions_back_to_top"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Back to top Transition time.
    $form['ife_transitions_back_to_top_time'] = [
      '#type' => 'number',
      '#default_value' => $config->get('ife_transitions_back_to_top_time'),
      '#title' => $this->t('Back To Top Duration'),
      '#description' => $this->t('Duration(time in Milliseconds) to reach top of page.'),
      '#states' => [
        'visible' => [
          'input[name="ife_transitions_back_to_top"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Throw errors when number fields are less than 0.
    if ($form_state->getValue('ife_transitions_time') < 0) {
      $form_state->setErrorByName('ife_transitions_time', $this->t('Transition duration should be greater than 0.'));
    }
    if ($form_state->getValue('ife_transitions_back_to_top_time') < 0) {
      $form_state->setErrorByName('ife_transitions_back_to_top_time', $this->t('Back to top duration should be greater than 0.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    \Drupal::configFactory()->getEditable('ife_transitions.settings')
      ->set('ife_transitions_time', (int) $values['ife_transitions_time'])
      ->set('ife_transitions_back_to_top', $values['ife_transitions_back_to_top'])
      ->set('ife_transitions_back_to_top_text', $values['ife_transitions_back_to_top_text'])
      ->set('ife_transitions_back_to_top_time', (int) $values['ife_transitions_back_to_top_time'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
