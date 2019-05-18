<?php

namespace Drupal\entity_delete_op\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_delete_op\DeleteManagerInterface;
use Drupal\entity_delete_op\EntityDeletableInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for overriding standard entity delete operation.
 */
class DeleteController extends ControllerBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The delete manager.
   *
   * @var \Drupal\entity_delete_op\DeleteManagerInterface
   */
  protected $deleteManager;

  /**
   * Creates a new instance of DeleteController.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\entity_delete_op\DeleteManagerInterface $delete_manager
   *   The delete manager.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, DeleteManagerInterface $delete_manager) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->deleteManager = $delete_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('entity_delete_op.manager')
    );
  }

  /**
   * Performs entity deletion with the delete manager.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns the redirect response upon success.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown if the entity is not found.
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function deleteEntity() {
    $parameters = $this->routeMatch->getParameters()->all();
    $entities = array_filter($parameters, function ($parameter) {
      return $parameter instanceof EntityDeletableInterface;
    });
    $entity = reset($entities);

    if (empty($entity)) {
      throw new NotFoundHttpException();
    }

    $this->deleteManager->delete($entity);

    $this->messenger()->addStatus($this->t('%label has been successfully updated.', [
      '%label' => $entity->label(),
    ]));

    $redirect_url = $this->getRedirectUrl($entity);
    return $this->redirect($redirect_url->getRouteName(), $redirect_url->getRouteParameters());
  }

  /**
   * Generates the redirect URL for the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Url
   *   The URL to redirect to.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Thrown if EntityInterface::toUrl() is unable to be performed adequately.
   */
  protected function getRedirectUrl(EntityInterface $entity) {
    if ($entity->hasLinkTemplate('collection')) {
      return $entity->toUrl('collection');
    }
    return Url::fromRoute('<front>');
  }

}
