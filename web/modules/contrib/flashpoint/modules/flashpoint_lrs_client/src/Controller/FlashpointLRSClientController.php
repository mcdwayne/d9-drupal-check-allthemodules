<?php
/**
 * @file
 */

namespace Drupal\flashpoint_lrs_client\Controller;

use Drupal\flashpoint_lrs_client\FlashpointLRSClientManager;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FlashpointLRSClientController
 *
 * Provides the route and API controller for flashpoint_lrs_client.
 */
class FlashpointLRSClientController extends ControllerBase {

  protected $FlashpointLRSClientManager; //The plugin manager.

  /**
   * Constructor.
   *
   * @param \Drupal\flashpoint_lrs_client\FlashpointLRSClientManager $plugin_manager
   */

  public function __construct(FlashpointLRSClientManager $plugin_manager) {
    $this->FlashpointLRSClientManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Use the service container to instantiate a new instance of our controller.
    return new static($container->get('plugin.manager.flashpoint_lrs_client'));
  }

}