<?php

namespace Drupal\foundation_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Layout\LayoutDefault;

/**
 * Layout class for all Foundation layouts.
 */
class FoundationLayouts extends LayoutDefault implements PluginFormInterface {


  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'wrappers' => [],
      'wrapper_classes' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $regions = $this->getPluginDefinition()->getRegions();

    $form['attributes'] = [
      '#group' => 'additional_settings',
      '#type' => 'details',
      '#title' => $this->t('Wrapper attributes'),
      '#description' => $this->t('Attributes for the outermost element'),
      '#tree' => TRUE,
    ];

    $form['attributes']['wrapper_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper classes'),
      '#description' => $this->t('Add additional classes to the outermost element.'),
      '#default_value' => $configuration['wrapper_classes'],
      '#weight' => 1,
    ];

    $form['attributes']['wrapper_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper Id'),
      '#description' => $this->t('Add an Id to the outermost element.'),
      '#default_value' => $configuration['wrapper_id'],
      '#weight' => 1,
    ];

    // Add wrappers.
    $wrapper_options = [
      'div' => 'Div',
      'section' => 'Section',
      'header' => 'Header',
      'footer' => 'Footer',
      'aside' => 'Aside',
    ];

    $form['region_wrapper'] = [
      '#group' => 'additional_settings',
      '#type' => 'details',
      '#title' => $this->t('Custom wrappers'),
      '#description' => $this->t('Choose a wrapper'),
      '#tree' => TRUE,
    ];

    foreach ($regions as $region_name => $region_definition) {
      $form['region_wrapper'][$region_name] = [
        '#type' => 'select',
        '#options' => $wrapper_options,
        '#title' => $this->t('Wrapper for @region', ['@region' => $region_definition['label']]),
        '#default_value' => !empty($configuration['wrappers'][$region_name]) ? $configuration['wrappers'][$region_name] : 'div',
      ];
    }

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
    $this->configuration['attributes'] = $form_state->getValue('attributes');
    foreach (['wrapper_classes', 'wrapper_id'] as $name) {
      $this->configuration[$name] = $this->configuration['attributes'][$name];
      unset($this->configuration['attributes'][$name]);
    }

    $this->configuration['wrappers'] = $form_state->getValue('region_wrapper');
  }

}
