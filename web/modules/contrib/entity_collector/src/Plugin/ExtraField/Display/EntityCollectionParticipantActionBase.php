<?php
/**
 * Created by PhpStorm.
 * User: leonvanderhaas
 * Date: 17/12/2018
 * Time: 11:43
 */

namespace Drupal\entity_collector\Plugin\ExtraField\Display;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_collector\Entity\EntityCollectionInterface;
use Drupal\entity_collector\Entity\EntityCollectionType;
use Drupal\entity_collector\Entity\EntityCollectionTypeInterface;
use Drupal\entity_collector\Service\EntityCollectionManagerInterface;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityCollectionParticipantActionBase
 *
 * @package Drupal\entity_collector\Plugin\ExtraField\Display
 */
abstract class EntityCollectionParticipantActionBase extends ExtraFieldDisplayFormattedBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Entity Collection Manager.
   *
   * @var EntityCollectionManagerInterface
   */
  protected $entityCollectionManager;

  /**
   * Route Provider.
   *
   * @var RouteProvider
   */
  protected $routeProvider;

  /**
   * Current User.
   *
   * @var AccountInterface
   */
  protected $currentUser;

  /**
   * Current Route Match.
   *
   * @var CurrentRouteMatch
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
   * EntityCollectionParticipantActionBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param $plugin_id
   *   The plugin_id for the plugin instance.
   * @param $plugin_definition
   *   The plugin implementaion definition.
   * @param EntityCollectionManagerInterface $entityCollectionManager
   * @param RouteProvider $routeProvider
   * @param AccountInterface $currentUser
   * @param CurrentRouteMatch $currentRouteMatch
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityCollectionManagerInterface $entityCollectionManager, RouteProvider $routeProvider, AccountInterface $currentUser, CurrentRouteMatch $currentRouteMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityCollectionManager = $entityCollectionManager;
    $this->routeProvider = $routeProvider;
    $this->routeName = 'entity_collector.participant.' . $this->action . '_me';
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
    $entityCollectionType = EntityCollectionType::load($entity->bundle());

    $field = $this->getActionLinkRenderArray($entityCollectionType, $entity);
    $field['#link']['#attributes']['class'][] = 'use-ajax';

    if (!$this->applyHiddenClass($entity, $this->currentUser)) {
      return $field;

    }

    $field['#link']['#attributes']['class'][] = 'visually-hidden';
    return $field;
  }

  /**
   * Return the render array of the link.
   *
   * @param EntityCollectionTypeInterface $entityCollectionType
   * @param EntityCollectionInterface $entityCollection
   *
   * @return mixed
   */
  abstract protected function getActionLinkRenderArray(EntityCollectionTypeInterface $entityCollectionType, EntityCollectionInterface $entityCollection);

  /**
   * Apply a visually hidden class if the link should be hidden.
   *
   * @param EntityCollectionInterface|null $entityCollection
   * @param AccountInterface $user
   *
   * @return bool
   */
  abstract protected function applyHiddenClass(EntityCollectionInterface $entityCollection = NULL, AccountInterface $user);

  /**
   * Return the default base field render array.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return array
   */
  protected function getBaseField(EntityCollectionTypeInterface $entityCollectionType) {
    $id = 'collection-' . $this->action . '-participant-' . $entityCollectionType->id();
    $field = [
      '#theme' => 'entity_collection_' . $this->action . '_participant',
      '#view_mode' => $this->viewMode,
      '#link' => [
        '#title' => $this->getActionLinkTitle($entityCollectionType),
        '#type' => 'link',
        '#attributes' => [
          'class' => [
            'entity-collection-type-' . $entityCollectionType->id(),
            'js-entity-collection-action-' . $this->action . '-participant',
          ],
          'data-base-url' => $this->getBaseUrlCollectionLink(),
          'id' => Html::getUniqueId($id),
        ],
      ],
      '#attached' => [
        'library' => [
          'entity_collector/entity-collection-participant-operations',
          'core/drupal.ajax',
        ],
      ],
      '#cache' => [
        'tags' => $this->entityCollectionManager->getListCacheTags($entityCollectionType->id(), $this->currentUser->id()),
      ],
    ];

    return $field;
  }

  /**
   * Get the title of the action link.
   *
   * @param EntityCollectionTypeInterface $entityCollectionType
   *
   * @return string
   */
  abstract protected function getActionLinkTitle(EntityCollectionTypeInterface $entityCollectionType);

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

    return implode('/', $routeParts);
  }

}