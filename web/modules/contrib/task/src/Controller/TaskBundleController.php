<?php
/**
 * @file
 */

namespace Drupal\task\Controller;

use Drupal\task\TaskBundleManager;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TaskBundleController
 *
 * Provides the route and API controller for task.
 */
class TaskBundleController extends ControllerBase
{

  protected $TaskBundleManager; //The plugin manager.

  /**
   * Constructor.
   *
   * @param \Drupal\task\TaskBundleManager $plugin_manager
   */

  public function __construct(TaskBundleManager $plugin_manager) {
    $this->TaskBundleManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Use the service container to instantiate a new instance of our controller.
    return new static($container->get('plugin.manager.task_bundle'));
  }

}
