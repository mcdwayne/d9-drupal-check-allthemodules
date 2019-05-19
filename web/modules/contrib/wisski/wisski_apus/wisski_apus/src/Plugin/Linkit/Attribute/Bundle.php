<?php

/**
 * @file
 * Contains \Drupal\wisski_apus\Plugin\Linkit\Attribute\Bundle.
 */

namespace Drupal\wisski_apus\Plugin\Linkit\Attribute;

use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\ConfigurableAttributeBase;

/**
 * Bundle attribute.
 *
 * @Attribute(
 *   id = "bundle",
 *   label = @Translation("Bundle"),
 *   html_name = "bundle",
 *   description = @Translation("Basic input field for the bundle attribute.")
 * )
 */
class Bundle extends ConfigurableAttributeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFormElement($default_value) {
    $element = [
      '#type' => 'textfield',
      '#title' => t('Bundle'),
      '#default_value' => $default_value,
      '#maxlength' => 255,
      '#size' => 40,
      '#placeholder' => t('The "bundle" attribute value'),
      '#attributes' => array(
      ),
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
