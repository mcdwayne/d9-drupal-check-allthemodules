<?php

namespace Drupal\tour_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tour UI Controller.
 */
class TourUIController extends ControllerBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * Returns list of modules included as part of the URL string.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request Service.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return list in JSON format.
   */
  public function getModules(Request $request) {
    $matches = [];

    $part = $request->query->get('q');
    if ($part) {
      $matches[] = $part;

      // Escape user input.
      $part = preg_quote($part);

      $modules = $this->moduleHandler->getModuleList();
      foreach ($modules as $module => $data) {
        if (preg_match("/$part/", $module)) {
          $matches[] = $module;
        }
      }
    }

    return new JsonResponse($matches);

  }

}
