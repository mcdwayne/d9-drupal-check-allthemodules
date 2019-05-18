<?php
/**
 * @file
 */

namespace Drupal\flashpoint\Controller;

use Drupal\flashpoint\FlashpointAccessMethodManager;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FlashpointAccessMethodController
 *
 * Provides the route and API controller for flashpoint_access.
 */
class FlashpointAccessMethodController extends ControllerBase
{

  protected $FlashpointAccessMethodManager; //The plugin manager.

  /**
   * Constructor.
   *
   * @param \Drupal\flashpoint\FlashpointAccessMethodManager $plugin_manager
   */

  public function __construct(FlashpointAccessMethodManager $plugin_manager) {
    $this->FlashpointAccessMethodManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Use the service container to instantiate a new instance of our controller.
    return new static($container->get('plugin.manager.flashpoint_access'));
  }
}