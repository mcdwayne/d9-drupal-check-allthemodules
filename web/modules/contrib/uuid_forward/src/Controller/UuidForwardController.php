<?php

namespace Drupal\uuid_forward\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * An example controller.
 */
class UuidForwardController extends ControllerBase {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * UuidForwardController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, LoggerInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('logger.factory')->get('uuid_forward')
    );
  }

  /**
   * Forward a UUID path to its entity.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $uuid
   *   The UUID being requested.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function forward(Request $request, $uuid) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // @todo Consider opening up for configuration entities also.
      if ($entity_type instanceof ContentEntityTypeInterface) {
        try {
          $entity = $this->entityRepository->loadEntityByUuid($entity_type_id, $uuid);
          if ($entity) {
            $entity_url = $entity->toUrl()->toString();
            if ($query_string = $request->getQueryString()) {
              $entity_url .= '?' . $query_string;
            }
            return new RedirectResponse($entity_url);
          }
        } catch (\Exception $e) {
          $this->logger->error($e->getMessage());
          throw new NotFoundHttpException();
        }
      }
    }
    throw new NotFoundHttpException();
  }

}
