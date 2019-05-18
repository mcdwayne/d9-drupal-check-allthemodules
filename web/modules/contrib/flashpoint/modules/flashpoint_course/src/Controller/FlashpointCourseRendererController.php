<?php
/**
 * @file
 */

namespace Drupal\flashpoint_course\Controller;

use Drupal\flashpoint_course\FlashpointCourseRendererManager;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FlashpointCourseRendererController
 *
 * Provides the route and API controller for flashpoint_course.
 */
class FlashpointCourseRendererController extends ControllerBase
{

  protected $FlashpointCourseRendererManager; //The plugin manager.

  /**
   * Constructor.
   *
   * @param \Drupal\flashpoint_course\FlashpointCourseRendererManager $plugin_manager
   */

  public function __construct(FlashpointCourseRendererManager $plugin_manager) {
    $this->FlashpointCourseRendererManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Use the service container to instantiate a new instance of our controller.
    return new static($container->get('plugin.manager.flashpoint_course_renderer'));
  }
}