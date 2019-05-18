<?php

namespace Drupal\kudos_slider\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * @file
 * Contains \Drupal\kudos_slider\Form\KudosSliderForm.
 */
class KudosSliderForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kudos_slider_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'kudos_slider.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('kudos_slider.settings');
    $form['kudos_slider_no_of_slides'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Number of Slides'),
      '#required' => TRUE,
      '#default_value' => $config->get('kudos_slider_no_of_slides'),
      '#weight' => 0,
      '#description' => $this->t('Enter the number of slides to be included in the slider.'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ((!is_numeric($form['kudos_slider_no_of_slides']['#value'])) || ($form['kudos_slider_no_of_slides']['#value'] == 0)) {
      $form_state->setErrorByName('kudos_slider_no_of_slides', 'Number of Slides must be a numeric value greater than 0.');
    }
  }

  /**
   * Submit handler for Kudos Slider form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('kudos_slider.settings');
    $kudos_slider_no_of_slides = $form_state->getValue(['kudos_slider_no_of_slides']);
    $config->set('kudos_slider_no_of_slides', $kudos_slider_no_of_slides);
    $config->save();
    parent::submitForm($form, $form_state);
    drupal_flush_all_caches();
  }

}
