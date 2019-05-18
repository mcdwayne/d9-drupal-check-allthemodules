<?php
/**
 * @file
 */

namespace Drupal\flashpoint_course_content\Controller;

use Drupal\flashpoint_course_content\FlashpointCourseContentRendererManager;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FlashpointCourseContentRendererController
 *
 * Provides the route and API controller for flashpoint_course_content.
 */
class FlashpointCourseContentRendererController extends ControllerBase
{

  protected $FlashpointCourseContentRendererManager; //The plugin manager.

  /**
   * Constructor.
   *
   * @param \Drupal\flashpoint_course_content\FlashpointCourseContentRendererManager $plugin_manager
   */

  public function __construct(FlashpointCourseContentRendererManager $plugin_manager) {
    $this->FlashpointCourseContentRendererManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Use the service container to instantiate a new instance of our controller.
    return new static($container->get('plugin.manager.flashpoint_course_content'));
  }
}