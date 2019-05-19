<?php

namespace Drupal\trash\Controller;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\trash\TrashManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns response for entity delete route.
 */
class TrashDeleteController extends ControllerBase {

  /**
   * The route match.
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
   * The Content Moderation moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * The Entity Form Builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The Trash Manager service.
   *
   * @var \Drupal\trash\TrashManagerInterface
   */
  protected $trashManager;

  /**
   * Constructs a new TrashDeleteController object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The Content Moderation moderation information service.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The Entity Form Builder service.
   * @param \Drupal\trash\TrashManagerInterface $trash_manager
   *   The Trash Manager service.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, ModerationInformationInterface $moderation_information, EntityFormBuilderInterface $entity_form_builder, TrashManagerInterface $trash_manager) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInformation = $moderation_information;
    $this->entityFormBuilder = $entity_form_builder;
    $this->trashManager = $trash_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('content_moderation.moderation_information'),
      $container->get('entity.form_builder'),
      $container->get('trash.manager')
    );
  }

  /**
   * Move an entity to trash.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   Returns the redirect response if the entity is moderateable, otherwise
   *   the delete form of the entity.
   */
  public function trashEntity() {
    $parameters = $this->routeMatch->getParameters()->all();
    $entities = array_filter($parameters, function ($parameter) {
      return $parameter instanceof ContentEntityInterface;
    });
    $entity = reset($entities);

    // Return the entity delete form for non-moderated entities.
    if (!$this->moderationInformation->isModeratedEntity($entity)) {
      return $this->entityFormBuilder->getForm($entity, 'delete');
    }

    $this->trashManager->trash($entity);

    drupal_set_message($this->t('The @entity %label has been moved to the trash. <a href=":undo-page">Undo</a>', [
      '@entity' => $entity->getEntityType()
        ->getLabel(),
      '%label' => $entity->label(),
      ':undo-page' => Url::fromRoute('trash.restore_form', [
        'entity_type_id' => $entity->getEntityTypeId(),
        'entity_id' => $entity->id(),
      ])->toString(),
    ]));
    $redirect_url = $this->getRedirectUrl($entity);

    return $this->redirect($redirect_url->getRouteName(), $redirect_url->getRouteParameters());
  }

  /**
   * Returns the url object for redirect path.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which we want the redirect url.
   *
   * @return \Drupal\Core\Url
   *   The redirect URL object.
   */
  protected function getRedirectUrl(ContentEntityInterface $entity) {
    if ($entity->hasLinkTemplate('collection')) {
      // If available, return the collection URL.
      return $entity->toUrl('collection');
    }
    else {
      // Otherwise fall back to the front page.
      return Url::fromRoute('<front>');
    }
  }

}
