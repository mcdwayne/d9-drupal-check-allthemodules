<?php
/**
 * @file
 */

namespace Drupal\entity_expiration\Controller;

use Drupal\entity_expiration\EntityExpirationMethodManager;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityExpirationController
 *
 * Provides the route and API controller for entity_expiration.
 */
class EntityExpirationController extends ControllerBase
{

  protected $entityExpirationMethodManager; //The plugin manager.

  /**
   * Constructor.
   *
   * @param \Drupal\entity_expiration\EntityExpirationMethodManager $plugin_manager
   */

  public function __construct(EntityExpirationMethodManager $plugin_manager) {
    $this->entityExpirationMethodManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   * This is dependancy injection at work for a controller. Rather than access the global service container via \Drupal::service(), it's best practice to use dependency injection.
   */
  public static function create(ContainerInterface $container) {
    // Use the service container to instantiate a new instance of our controller.
    return new static($container->get('plugin.manager.entity_expiration_method'));
  }
}