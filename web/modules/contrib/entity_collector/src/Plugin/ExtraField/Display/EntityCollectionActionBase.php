<?php

namespace Drupal\entity_collector\Plugin\ExtraField\Display;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\entity_collector\Entity\EntityCollectionInterface;
use Drupal\entity_collector\Entity\EntityCollectionType;
use Drupal\entity_collector\Entity\EntityCollectionTypeInterface;
use Drupal\entity_collector\Service\EntityCollectionManager;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class EntityCollectionActionBase extends ExtraFieldDisplayFormattedBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Entity Collection Manager.
   *
   * @var \Drupal\entity_collector\Service\EntityCollectionManagerInterface
   */
  protected $entityCollectionManager;

  /**
   * Route Provider.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * Current User.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Current Route Match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Route name of the action.
   *
   * @var string
   */
  protected $routeName;

  /**
   * Type of action, add or remove.
   *
   * @var string
   */
  protected $action;

  /**
   * Constructs a ExtraFieldDisplayFormattedBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityCollectionManager $entityCollectionManager, RouteProvider $routeProvider, AccountInterface $currentUser, CurrentRouteMatch $currentRouteMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityCollectionManager = $entityCollectionManager;
    $this->routeProvider = $routeProvider;
    $this->routeName = 'entity_collector.item.' . $this->action;
    $this->currentUser = $currentUser;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_collection.manager'),
      $container->get('router.route_provider'),
      $container->get('current_user'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelDisplay() {
    return 'hidden';
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity) {
    $definition = $this->getPluginDefinition();
    $entityCollectionType = EntityCollectionType::load($definition['entity_collection_type']);

    $entityCollection = $this->currentRouteMatch->getParameter('entity_collection');

    if(empty($entityCollection) || !$entityCollection instanceof EntityCollectionInterface) {
      $entityCollection = $this->entityCollectionManager->getActiveCollection($entityCollectionType);
    }

    if (empty($entityCollection)) {
      $field = $this->getSelectCollectionLinkRenderArray($entityCollectionType, $entity);
    }
    else {
      $field = $this->getActionLinkRenderArray($entityCollectionType, $entityCollection, $entity);
      $field['#link']['#attributes']['class'][] = 'use-ajax';
    }

    if ($this->applyHiddenClass($entityCollectionType, $entityCollection, $entity)) {
      $field['#link']['#attributes']['class'][] = 'visually-hidden';
    }

    return $field;
  }

  /**
   * Return the render array of the link.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return mixed
   */
  protected function getSelectCollectionLinkRenderArray(EntityCollectionTypeInterface $entityCollectionType, ContentEntityInterface $entity) {
    $field = $this->getBaseField($entityCollectionType, $entity);
    $field['#link']['#url'] = Url::fromUserInput('#entity-collection-selection-modal');
    $field['#link']['#attributes']['data-toggle'] = "modal";
    $field['#link']['#attributes']['data-target'] = "#entity-collection-selection-modal";

    return $field;
  }

  /**
   * Return the default base field render array.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return array
   */
  protected function getBaseField(EntityCollectionTypeInterface $entityCollectionType, ContentEntityInterface $entity) {
    $id = 'collection-' . $this->action . '-' . $entityCollectionType->id() . '-' . $entity->id();
    $field = [
      '#theme' => 'entity_collection_' . $this->action . '_entity_field',
      '#view_mode' => $this->viewMode,
      '#link' => [
        '#title' => $this->getActionLinkTitle($entityCollectionType),
        '#type' => 'link',
        '#attributes' => [
          'class' => [
            'entity-collection-item-' . $entity->id(),
            'entity-collection-type-' . $entityCollectionType->id(),
            'js-entity-collection-action-' . $this->action,
          ],
          'data-entity' => $entity->id(),
          'data-base-url' => $this->getBaseUrlCollectionLink(),
          'id' => Html::getUniqueId($id),
        ],
      ],
      '#attached' => [
        'library' => [
          'entity_collector/entity-collection-field-operations',
          'core/drupal.ajax',
        ],
      ],
      '#cache' => [
        'tags' => $this->entityCollectionManager->getListCacheTags($entityCollectionType->id(), $this->currentUser->id())
      ]
    ];

    return $field;
  }

  /**
   * Get the base url.
   *
   * @return string
   */
  protected function getBaseUrlCollectionLink() {
    $route = $this->routeProvider->getRouteByName($this->routeName);
    $routeParts = explode('/', $route->getPath());
    $routeParts = array_combine($routeParts, $routeParts);
    unset($routeParts['{entityCollectionId}']);
    unset($routeParts['{entityId}']);

    return implode('/', $routeParts);
  }

  /**
   * Return the render array of the link.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   * @param \Drupal\entity_collector\Entity\EntityCollectionInterface $entityCollection
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return mixed
   */
  abstract protected function getActionLinkRenderArray(EntityCollectionTypeInterface $entityCollectionType, EntityCollectionInterface $entityCollection, ContentEntityInterface $entity);

  /**
   * Get the title of the action link.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   *
   * @return string
   */
  abstract protected function getActionLinkTitle(EntityCollectionTypeInterface $entityCollectionType);

  /**
   * Apply a visually hidden class if the link should be hidden.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionInterface $entityCollection
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return bool
   */
  abstract protected function applyHiddenClass(EntityCollectionTypeInterface $entityCollectionType, EntityCollectionInterface $entityCollection = NULL, ContentEntityInterface $entity);

}
