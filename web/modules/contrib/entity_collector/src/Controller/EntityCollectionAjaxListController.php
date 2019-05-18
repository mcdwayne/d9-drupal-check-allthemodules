<?php

namespace Drupal\entity_collector\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_collector\Entity\EntityCollectionInterface;
use Drupal\entity_collector\Entity\EntityCollectionTypeInterface;
use Drupal\entity_collector\Service\EntityCollectionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

class EntityCollectionAjaxListController extends EntityCollectionControllerBase implements ContainerInjectionInterface {

  /**
   * Plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  private $pluginManager;

  /**
   * Render.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  private $renderer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * EntityCollectorApiController constructor.
   *
   * @param \Drupal\entity_collector\Service\EntityCollectionManagerInterface $entityCollectionManager
   *   The entity collection manager.
   * @param \Drupal\Core\Session\AccountInterface|\Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(EntityCollectionManagerInterface $entityCollectionManager, AccountProxyInterface $currentUser, RequestStack $requestStack, PluginManagerInterface $pluginManager, EntityTypeManagerInterface $entityTypeManager, RendererInterface $renderer) {
    parent::__construct($entityCollectionManager, $currentUser, $requestStack);
    $this->pluginManager = $pluginManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->renderer = $renderer;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_collection.manager'),
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('plugin.manager.block'),
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Set the active collection.
   *
   * @param string $entityCollectionTypeId
   * @param int $entityCollectionId
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Drupal\Core\Ajax\AjaxResponse
   *   The response.
   * @throws \Exception
   */
  public function setActiveCollection($entityCollectionTypeId, $entityCollectionId) {
    $request = $this->requestStack->getCurrentRequest();
    $response = new RedirectResponse($request->headers->get('referer'));
    $lockName = 'entity_collection_active_' . $this->currentUser->id();
    $this->entityCollectionManager->acquireLock($lockName);

    try {
      $entityCollectionType = $this->entityCollectionManager->getEntityCollectionType($entityCollectionTypeId);
      $entityCollection = $this->entityCollectionManager->getEntityCollection($entityCollectionId);
      $this->entityCollectionManager->setActiveCollection($entityCollectionType, $entityCollection);

      $request = $this->requestStack->getCurrentRequest();
      if ($request->isXmlHttpRequest()) {
        $response = new AjaxResponse();

        $triggerParam = [$entityCollection->bundle(), $entityCollection->id()];
        $response->addCommand(new InvokeCommand('.js-entity-collection.entity-collection-type-' . $entityCollection->bundle(), 'removeClass', ['active']));
        $response->addCommand(new InvokeCommand('.js-entity-collection.entity-collection-' . $entityCollection->id() . '.entity-collection-type-' . $entityCollection->bundle(), 'addClass', ['active']));
        $response->addCommand(new InvokeCommand('.entity-collection-type-' . $entityCollection->bundle(), 'attr', [
          'data-entity-collection',
          $entityCollection->id(),
        ]));

        $response = $this->showCorrectFieldsForEntities($response, $entityCollection);

        $response->addCommand(new InvokeCommand('.js-entity-collection.entity-collection-' . $entityCollection->id() . '.entity-collection-type-' . $entityCollection->bundle(), 'trigger', [
          'activatedEntityCollection',
          $triggerParam,
        ]));
      }
    } finally {
      $this->entityCollectionManager->releaseLock($lockName);
    }

    return $response;
  }

  /**
   * Show the correct fields for the entities.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   * @param \Drupal\entity_collector\Entity\EntityCollectionInterface $entityCollection
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  private function showCorrectFieldsForEntities(AjaxResponse $response, EntityCollectionInterface $entityCollection) {
    $fieldDefinition = $this->entityCollectionManager->getSourceFieldDefinition($entityCollection);
    $entityIds = array_map(function ($value) {
      return $value['target_id'];
    },
      $entityCollection->get($fieldDefinition->getName())->getValue()
    );

    $actionSelectors = '.entity-collection-type-' . $entityCollection->bundle() . '.js-entity-collection-action-add, .entity-collection-type-' . $entityCollection->bundle() . '.js-entity-collection-action-remove';
    $response->addCommand(new InvokeCommand($actionSelectors, 'removeAttr', ['data-toggle']));
    $response->addCommand(new InvokeCommand($actionSelectors, 'removeAttr', ['data-target']));
    $response->addCommand(new InvokeCommand($actionSelectors, 'addClass', ['use-ajax']));
    $response->addCommand(new InvokeCommand('.entity-collection-type-' . $entityCollection->bundle() . '.js-entity-collection-action-add', 'removeClass', ['visually-hidden']));
    $response->addCommand(new InvokeCommand('.entity-collection-type-' . $entityCollection->bundle() . '.js-entity-collection-action-remove', 'addClass', ['visually-hidden']));

    foreach ($entityIds as $entityId) {
      $response->addCommand(new InvokeCommand('.entity-collection-item-' . $entityId . '.entity-collection-type-' . $entityCollection->bundle() . '.js-entity-collection-action-add', 'addClass', ['visually-hidden']));
      $response->addCommand(new InvokeCommand('.entity-collection-item-' . $entityId . '.entity-collection-type-' . $entityCollection->bundle() . '.js-entity-collection-action-remove', 'removeClass', ['visually-hidden']));
    }

    return $response;
  }

  /**
   * Get the active collection.
   *
   * @param string $entityCollectionTypeId
   *   The entity collection type.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The http response.
   * @throws \Exception
   */
  public function getActiveCollection(EntityCollectionTypeInterface $entityCollectionTypeId) {
    $lockName = 'entity_collection_active_' . $this->currentUser->id();
    $this->entityCollectionManager->acquireLock($lockName);

    try {
      $entityCollectionType = $this->entityCollectionManager->getEntityCollectionType($entityCollectionTypeId);
      $entityCollection = $this->entityCollectionManager->getActiveCollection($entityCollectionType);
    } finally {
      $this->entityCollectionManager->releaseLock($lockName);
    }

    return new JsonResponse($entityCollection);
  }

  /**
   * Get the collections.
   *
   * @param string $entityCollectionTypeId
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The http response.
   * @throws \Exception
   */
  public function getCollections($entityCollectionTypeId) {
    $lockName = 'entity_collection_list_' . $this->currentUser->id();
    $this->entityCollectionManager->acquireLock($lockName);

    try {
      $entityCollectionType = $this->entityCollectionManager->getEntityCollectionType($entityCollectionTypeId);
      $collections = $this->entityCollectionManager->getCollections($entityCollectionType);

      $options = [];
      foreach ($collections as $entityCollection) {
        $options[$entityCollection->id()] = $entityCollection->label();
      }
    } finally {
      $this->entityCollectionManager->releaseLock($lockName);
    }

    return new JsonResponse($options);
  }

}
