<?php

namespace Drupal\prod_check\Plugin\ProdCheckProcessor;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\prod_check\Plugin\ProdCheckCategoryPluginManager;
use Drupal\prod_check\Plugin\ProdCheckPluginManager;

/**
 * Provides a base implementation for a configurable Production check processor plugin.
 */
abstract class ConfigurableProdCheckProcessorBase extends ProdCheckProcessorBase implements ConfigurablePluginInterface, PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ProdCheckPluginManager $manager, ProdCheckCategoryPluginManager $category_manager, QueryFactory $query_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $manager, $category_manager, $query_service);

    $this->configuration += $this->defaultConfiguration();
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
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
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
