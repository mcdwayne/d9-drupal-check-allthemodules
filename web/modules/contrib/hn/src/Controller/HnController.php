<?php

namespace Drupal\hn\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hn\HnResponseService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * The Hn Controller provides the endpoint function.
 *
 * The endpoint function gets invoked when the /hn endpoint is loaded.
 *
 * @package Drupal\hn\Controller
 */
class HnController extends ControllerBase {

  /**
   * The hn response service.
   *
   * @var \Drupal\hn\HnResponseService
   */
  protected $hnResponseService;

  /**
   * HnController constructor.
   *
   * @param \Drupal\hn\HnResponseService $hnResponseService
   *   The response service.
   */
  public function __construct(HnResponseService $hnResponseService) {
    $this->hnResponseService = $hnResponseService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('hn.response')
    );
  }

  /**
   * This is the function that gets called when the /hn endpoint is used.
   */
  public function endpoint() {
    $response = new JsonResponse($this->hnResponseService->getResponseData());
    $response->headers->set('Cache-Control', 'public, max-age=3600');
    return $response;
  }

}
