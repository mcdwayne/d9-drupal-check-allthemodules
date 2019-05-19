<?php

/**
 * @file
 * Contains \Drupal\wisski_apus\Plugin\Linkit\Attribute\Evidence.
 */

namespace Drupal\wisski_apus\Plugin\Linkit\Attribute;

use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\ConfigurableAttributeBase;

/**
 * Evidence attribute.
 *
 * @Attribute(
 *   id = "evidence",
 *   label = @Translation("Evidence"),
 *   html_name = "data-wisski-evidence",
 *   description = @Translation("Basic input field for the evidence attribute.")
 * )
 */
class Evidence extends ConfigurableAttributeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFormElement($default_value) {
    
    $options = array(
      'automatic_analysis' => $this->t('Automatic analysis'),
      'user' => $this->t('User'),
      'other_source' => $this->t('Other Source'),
    );
    
    $element = [
      '#type' => 'select',
      '#title' => t('Evidence'),
      '#default_value' => $default_value,
      '#options' => $options,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = array();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
