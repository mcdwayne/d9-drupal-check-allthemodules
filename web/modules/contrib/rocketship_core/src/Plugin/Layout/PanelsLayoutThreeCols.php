<?php

namespace Drupal\rocketship_core\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Class PanelsLayoutOneCol.
 *
 * @package Drupal\rocketship_core\Plugin\Layout
 */
class PanelsLayoutThreeCols extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration += [
      'layout' => [
        'extra_classes' => '',
      ],
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $complete_form_state = $form_state instanceof SubformStateInterface ?
      $form_state->getCompleteFormState() : $form_state;
    $configuration = $this->getConfiguration();

    $form['layout']['extra_classes'] = [
      '#type' => 'textfield',
      '#title' => 'Extra classes',
      '#description' => 'Add extra classes (separate using spaces) to the template',
      '#default_value' => $complete_form_state->getValue([
        'layout',
        'extra_classes',
      ], $configuration['layout']['extra_classes']),
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
    $this->configuration['layout'] = $form_state->getValue('layout');
  }

}
