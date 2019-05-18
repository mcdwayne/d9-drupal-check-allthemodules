<?php

namespace Drupal\opcache_gui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\opcache_gui\OpCache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class OpcacheController.
 *
 * @package Drupal\opcache_gui\Controller
 */
class OpcacheController extends ControllerBase {

  /**
   * @var \Drupal\opcache_gui\OpCache
   */
  private $opCache;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  public function __construct(OpCache $opCache, RendererInterface $renderer) {
    $this->opCache = $opCache;
    $this->renderer = $renderer;
  }


  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('opcache_gui.opcache'),
      $container->get('renderer')
    );
  }

  public function info() {

    $build = [
      '#theme' => 'opcache_gui',
      '#opcache' => $this->opCache,
    ];

    $output = $this->renderer->renderRoot($build);

    return new Response($output);
  }

  public function state() {
    return new JsonResponse($this->opCache->getData());
  }
}
