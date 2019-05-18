<?php
/**
 * @file
 */

namespace Drupal\flashpoint\Controller;

use Drupal\flashpoint\FlashpointSettingsManager;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FlashpointSettingsController
 *
 * Provides the route and API controller for flashpoint_settings.
 */
class FlashpointSettingsController extends ControllerBase
{

  protected $FlashpointSettingsManager; //The plugin manager.

  /**
   * Constructor.
   *
   * @param \Drupal\flashpoint\FlashpointSettingsManager $plugin_manager
   */

  public function __construct(FlashpointSettingsManager $plugin_manager) {
    $this->FlashpointSettingsManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Use the service container to instantiate a new instance of our controller.
    return new static($container->get('plugin.manager.flashpoint_settings'));
  }
}