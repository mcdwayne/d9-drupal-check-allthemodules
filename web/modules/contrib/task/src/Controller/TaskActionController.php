<?php
/**
 * @file
 */

namespace Drupal\task\Controller;

use Drupal\task\TaskActionManager;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TaskActionController
 *
 * Provides the route and API controller for task.
 */
class TaskActionController extends ControllerBase
{

  protected $TaskActionManager; //The plugin manager.

  /**
   * Constructor.
   *
   * @param \Drupal\task\TaskActionManager $plugin_manager
   */

  public function __construct(TaskActionManager $plugin_manager) {
    $this->TaskActionManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Use the service container to instantiate a new instance of our controller.
    return new static($container->get('plugin.manager.task_action'));
  }

}
