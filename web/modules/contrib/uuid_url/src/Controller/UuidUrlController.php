<?php

namespace Drupal\uuid_url\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for uuid_url module routes.
 */
class UuidUrlController extends ControllerBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a \Drupal\aggregator\Controller\AggregatorController object.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * UUID route.
   *
   * @param string $entity_type
   *  The entity type ID.
   * @param string $uuid
   *  The UUID of an entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function uuidEntityRedirect($entity_type, $uuid) {
    $entity = $this->entityTypeManager->getStorage($entity_type)
      ->loadByProperties(['uuid' => $uuid]);
    $entity = reset($entity);
    if ($entity && $entity->access('view', $this->currentUser())) {
      $url = $entity->toUrl();
      $response = new RedirectResponse($url->toString());
      return $response;
    }
    throw new NotFoundHttpException();
  }

}
