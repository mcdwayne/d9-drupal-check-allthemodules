<?php

namespace Drupal\tasty_backend\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tasty_backend\TastyBackendManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TastyBackendController.
 */
class TastyBackendController extends ControllerBase {
  
  /**
   * Tasty Backend Manager Service.
   *
   * @var \Drupal\tasty_backend\manager\TastyBackendManager
   */
  protected $tastyBackendManager;
  
  /**
   * Constructs a new TastyBackendController.
   *
   * @param \Drupal\tasty_backend\manager\TastyBackendManager $tastyBackendManager
   *   Tasty Backend Manager service.
   */
  public function __construct(TastyBackendManager $tastyBackendManager) {
    $this->tastyBackendManager = $tastyBackendManager;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tasty_backend.manager')
    );
  }

  /**
   * Page to list all content management views.
   *
   * @return array
   *   A render array suitable for drupal_render.
   */
  public function menuBlockContents() {
    return $this->tastyBackendManager->getBlockContents();
  }

}
