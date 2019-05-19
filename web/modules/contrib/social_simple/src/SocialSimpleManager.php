<?php

namespace Drupal\social_simple;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\social_simple\SocialNetwork\SocialNetworkInterface;

/**
 * Provides a social network manager.
 *
 * Can be assigned any number of BreadcrumbBuilderInterface objects by calling
 * the addBuilder() method. When build() is called it iterates over the objects
 * in priority order and uses the first one that returns TRUE from
 * BreadcrumbBuilderInterface::applies() to build the breadcrumbs.
 *
 * @see \Drupal\Core\DependencyInjection\Compiler\RegisterBreadcrumbBuilderPass
 */
class SocialSimpleManager implements SocialSimpleManagerInterface {

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Holds arrays of social network builders, keyed by priority.
   *
   * @var array
   */
  protected $networks = [];

  /**
   * Holds the array of social network builders sorted by priority.
   *
   * Set to NULL if the array needs to be re-calculated.
   *
   * @var \Drupal\social_simple\SocialNetwork\SocialNetworkInterface[]|null
   */
  protected $sortedNetworks;

  /**
   * Constructs a \Drupal\Core\Breadcrumb\BreadcrumbManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function addNetwork(SocialNetworkInterface $network, $priority) {
    $network_id = $network->getId();
    $this->networks[$network_id][$priority][] = $network;

    // Force the builders to be re-sorted.
    $this->sortedNetworks = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildShareUrl($network_id) {

  }

  /**
   * {@inheritdoc}
   */
  public function getSortedNetworks() {
    if (!isset($this->sortedNetworks)) {
      foreach ($this->networks as $network_id => $networks_by_priority) {
        krsort($this->networks[$network_id]);
        $network_services = reset($this->networks[$network_id]);
        $this->sortedNetworks[$network_id] = reset($network_services);
      }
    }

    return $this->sortedNetworks;
  }

  /**
   * {@inheritdoc}
   */
  public function get($network_id) {
    if (!isset($this->sortedNetworks[$network_id])) {
      return [];
    }
    return $this->sortedNetworks[$network_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getNetworks() {
    foreach ($this->getSortedNetworks() as $id => $network) {
      $networks[$id] = $network->getLabel();
    }
    return $networks;
  }

}
