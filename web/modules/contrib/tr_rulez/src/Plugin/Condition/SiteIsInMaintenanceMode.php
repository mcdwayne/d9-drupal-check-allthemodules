<?php

namespace Drupal\tr_rulez\Plugin\Condition;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\rules\Core\RulesConditionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Site is in maintenance mode' condition.
 *
 * @Condition(
 *   id = "rules_site_is_in_maintenance_mode",
 *   label = @Translation("Site is in maintenance mode"),
 *   category = @Translation("System")
 * )
 *
 * @todo: Add access callback information from Drupal 7.
 */
class SiteIsInMaintenanceMode extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateService;

  /**
   * Constructs a SiteIsInMaintenanceMode object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\State\StateInterface $state_service
   *   The state service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stateService = $state_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state')
    );
  }

  /**
   * Checks if the site is in maintenance mode.
   *
   * @return bool
   *   TRUE if the site is in maintenance mode.
   */
  protected function doEvaluate() {
    return (bool) $this->stateService->get('system.maintenance_mode');
  }

}
