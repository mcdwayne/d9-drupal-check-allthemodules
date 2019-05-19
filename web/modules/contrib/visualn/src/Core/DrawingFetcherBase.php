<?php

namespace Drupal\visualn\Core;

use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\Core\DrawingFetcherInterface;
use Drupal\visualn\WindowParametersTrait;

/**
 * Base class for VisualN Drawing Fetcher plugins.
 */
abstract class DrawingFetcherBase extends ContextAwarePluginBase implements DrawingFetcherInterface {

  use WindowParametersTrait;

  // @todo: override setWindowParameters() for the case when fetcher is used to generate
  //   drawing build by its own, without drawer plugin
  //   @see \Drupal\visualn\Core\DrawerBase::setWindowParameters()

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchDrawing() {
    return ['#markup' => t('no markup for the drawing')];
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

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
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
    // @todo: use NestedArray::mergeDeep here. See BlockBase::setConfiguration for example.
    // @todo: also do the same for all other plugin types
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
