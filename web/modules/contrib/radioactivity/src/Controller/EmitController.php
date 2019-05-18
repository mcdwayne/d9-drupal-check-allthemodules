<?php

namespace Drupal\radioactivity\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\radioactivity\StorageFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Serialization\Json;
use Drupal\radioactivity\Incident;

/**
 * Controller routines for radioactivity emit routes.
 */
class EmitController implements ContainerInjectionInterface {

  /**
   * The incident storage.
   *
   * @var \Drupal\Radioactivity\IncidentStorageInterface
   */
  protected $incidentStorage;

  /**
   * Constructs an EmitController object.
   *
   * @param \Drupal\radioactivity\StorageFactory $storageFactory
   *   Radioactivity storage factory.
   */
  public function __construct(StorageFactory $storageFactory) {
    $this->incidentStorage = $storageFactory->get('default');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('radioactivity.storage')
    );
  }

  /**
   * Callback for /radioactivity/emit.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response object.
   */
  public function emit(Request $request) {

    $postData = $request->getContent();
    if (empty($postData)) {
      return new JsonResponse(['status' => 'error', 'message' => 'Empty request.']);
    }

    $count = 0;
    $incidents = Json::decode($postData);

    foreach ($incidents as $data) {

      $incident = Incident::createFromPostData($data);
      if (!$incident->isValid()) {
        return new JsonResponse(['status' => 'error', 'message' => 'invalid incident (' . $count . ').']);
      }

      $this->incidentStorage->addIncident($incident);
      $count++;
    }

    return new JsonResponse(['status' => 'ok', 'message' => $count . ' incidents added.']);
  }

}
