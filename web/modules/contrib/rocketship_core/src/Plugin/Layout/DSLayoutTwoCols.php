<?php

namespace Drupal\rocketship_core\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Class DSLayoutTwoCols.
 *
 * @package Drupal\rocketship_core\Plugin\Layout
 */
class DSLayoutTwoCols extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'layout_reversed' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $form['layout_reversed'] = [
      '#type' => 'checkbox',
      '#title' => 'Reverse this layout',
      '#description' => 'Reversing this layout will render the sidebar underneath the content on mobile devices.',
      '#default_value' => $configuration['layout_reversed'],
    ];
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
    $this->configuration['layout_reversed'] = $form_state->getValue('layout_reversed');
  }

}
