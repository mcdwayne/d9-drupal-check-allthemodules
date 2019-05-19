<?php

namespace Drupal\SimpleAds\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\simpleads\SimpleAds;

class SimpleAds extends ControllerBase {

  protected $renderer;

  public function __construct(Renderer $render) {
    $this->renderer = $render;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * AMP version of the node.
   */
  public function page(Request $request) {
    
  }

}
