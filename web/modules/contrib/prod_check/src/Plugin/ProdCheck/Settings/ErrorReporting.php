<?php

namespace Drupal\prod_check\Plugin\ProdCheck\Settings;

use Drupal\Core\Form\FormStateInterface;
use Drupal\prod_check\Plugin\ProdCheck\ProdCheckBase;

/**
 * User register settings check
 *
 * @ProdCheck(
 *   id = "error_reporting",
 *   title = @Translation("Error reporting"),
 *   category = "settings",
 * )
 */
class ErrorReporting extends ProdCheckBase {

  /**
   * The currently selected option
   */
  protected $current;

  /**
   * All the possible options
   */
  protected $options;

  /**
   * {@inheritdoc}
   */
  public function init() {
    $this->current = $this->configFactory->get('system.logging')->get('error_level');;

    $this->options = [
      ERROR_REPORTING_HIDE => $this->t('Display no errors'),
      ERROR_REPORTING_DISPLAY_SOME => $this->t('Display errors and warnings'),
      ERROR_REPORTING_DISPLAY_ALL => $this->t('Display all messages'),
      ERROR_REPORTING_DISPLAY_VERBOSE => $this->t('Display all messages, plus backtrace information')
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function state() {
    $options = $this->configuration['options'];

    return !empty($options[$this->current]);
  }

  /**
   * {@inheritdoc}
   */
  public function successMessages() {
    return [
      'value' => $this->options[$this->current],
      'description' => $this->generateDescription(
        $this->title(),
        'system.logging_settings'
      ),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function failMessages() {
    $link_array = $this->generateLinkArray($this->title(), 'system.logging_settings');

    return [
      'value' => $this->options[$this->current],
      'description' => $this->t(
        'Your %link settings are set to "@current". Are you sure this is what you want and did not mean to use @options?',
        [
          '%link' => implode($link_array),
          '@current' => $this->options[$this->current],
          '@options' => '"' . implode('" ' . t('or') . ' "', $this->getSelectedOptions()) . '"',
        ]
      )
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['options'] = [
      ERROR_REPORTING_HIDE => ERROR_REPORTING_HIDE,
      ERROR_REPORTING_DISPLAY_SOME => 0,
      ERROR_REPORTING_DISPLAY_ALL => 0,
      ERROR_REPORTING_DISPLAY_VERBOSE => 0
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['options'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Valid error reporting options'),
      '#default_value' => $this->configuration['options'],
      '#options' => $this->options,
      '#required' => TRUE,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['options'] = $form_state->getValue('options');
  }

  /**
   * Fetches all the selected options.
   */
  protected function getSelectedOptions() {
    $selected_options = [];
    foreach ($this->configuration['options'] as $option) {
      if (!empty($option)) {
        $selected_options[] = $this->options[$option];
      }
    }

    return $selected_options;
  }

}
