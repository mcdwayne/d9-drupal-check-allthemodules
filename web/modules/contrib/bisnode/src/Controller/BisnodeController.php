<?php

namespace Drupal\bisnode\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\bisnode\BisnodeServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BisnodeController.
 */
class BisnodeController extends ControllerBase {

  /**
   * Drupal\bisnode\BisnodeServiceInterface definition.
   *
   * @var \Drupal\bisnode\BisnodeServiceInterface
   */
  protected $bisnodeWebapi;
  /**
   * Constructs a new BisnodeTestConnectionForm object.
   */
  public function __construct(BisnodeServiceInterface $bisnode_webapi) {
    $this->bisnodeWebapi = $bisnode_webapi;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bisnode.webapi')
    );
  }

  /**
   * Search directory.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse.
   */
  public function searchDirectory(Request $request) {
    $data = [
      'results' => [],
    ];
    try {
      $string = $request->request->get('string', '');
      if (trim($string)) {
        $results = $this->bisnodeWebapi->getDirectory($string);
        $data['results'] = $results;
      }
    }
    catch (\Exception $e) {
      // Do nothing.
    }

    return new JsonResponse($data);
  }

}
