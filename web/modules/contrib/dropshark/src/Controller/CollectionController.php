<?php

namespace Drupal\dropshark\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\dropshark\Collector\CollectorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class CollectionController.
 */
class CollectionController extends ControllerBase {

  /**
   * DropShark collector manager.
   *
   * @var \Drupal\dropshark\Collector\CollectorManager
   */
  protected $collectorManager;

  /**
   * CollectionController constructor.
   *
   * @param \Drupal\dropshark\Collector\CollectorManager $collectorManager
   *   Collector manager.
   */
  public function __construct(CollectorManager $collectorManager) {
    $this->collectorManager = $collectorManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.dropshark_collector')
    );
  }

  /**
   * Performs data collection.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function collect(Request $request) {
    $site_id = $this->state()->get('dropshark.site_id');
    $token = $this->state()->get('dropshark.site_token');

    if (!$site_id || !$token) {
      throw new NotFoundHttpException();
    }

    if ($site_id != $request->query->get('key')) {
      throw new NotFoundHttpException();
    }

    // Do some collecting.
    $this->collectorManager->collect(['all']);

    $response = new JsonResponse();
    $response->setData([
      'code' => 200,
      'result' => 'Data collection complete.',
      'timestamp' => date('c'),
    ]);

    return $response;
  }

}
