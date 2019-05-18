<?php

namespace Drupal\owntracks\Controller;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\owntracks\OwnTracksEndpointService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class OwnTracksEndpoint.
 */
class OwnTracksEndpoint extends ControllerBase {

  /**
   * The endpoint service.
   *
   * @var \Drupal\owntracks\OwnTracksEndpointService
   */
  protected $endpointService;

  /**
   * OwnTracksEndpoint constructor.
   *
   * @param \Drupal\owntracks\OwnTracksEndpointService $endpointService
   *   The owntracks endpoint service.
   */
  public function __construct(OwnTracksEndpointService $endpointService) {
    $this->endpointService = $endpointService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\owntracks\OwnTracksEndpointService $endpointService */
    $endpointService = $container->get('owntracks.endpoint_service');

    return new static(
      $endpointService
    );
  }

  /**
   * Handle owntracks endpoint requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The HTTP response.
   */
  public function handle(Request $request) {
    try {
      $this->endpointService->create($request->getContent());
    }
    catch (InvalidDataTypeException $e) {
      throw new HttpException(400, $e->getMessage());
    }
    catch (\Exception $e) {
      throw new HttpException(500, $e->getMessage());
    }

    return new Response('{}', 200, ['Content-Type' => 'application/json']);
  }

}
