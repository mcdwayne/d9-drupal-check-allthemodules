<?php

/**
 * @file
 * Contains \Drupal\po_translations_report\DetailsDisplayerPluginBase.
 */

namespace Drupal\po_translations_report;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for plugins able to display content.
 *
 * @ingroup plugin_api
 */
abstract class DetailsDisplayerPluginBase extends PluginBase implements DetailsDisplayerPluginInterface {

  /**
   * Name of the config being edited.
   */
  const CONFIGNAME = 'po_translations_report.admin_config';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function display(array $results) {
    $display = array(
      '#type' => 'table',
      '#header' => array(),
      '#rows' => $results,
    );
    return $display;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration += $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $display_plugin_id = $form_state->getValue('details_display_method');
    $config = \Drupal::configFactory()
        ->getEditable(static::CONFIGNAME);
    $config->set($display_plugin_id . '_configuration', $this->configuration);
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

}
