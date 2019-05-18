<?php

namespace Drupal\entity_collector\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\entity_collector\Entity\EntityCollectionInterface;
use Drupal\entity_collector\Entity\EntityCollectionTypeInterface;
use Drupal\entity_collector\Service\EntityCollectionManager;

/**
 * Example Extra field Display.
 *
 * @ExtraFieldDisplay(
 *   id = "delete_entity_from_entity_collection",
 *   label = @Translation("Remove entity from collection."),
 *   deriver =
 *   "Drupal\entity_collector\Plugin\Derivative\EntityCollectionActionsDerivative"
 * )
 */
class RemoveEntityAction extends EntityCollectionActionBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * AddEntityAction constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\entity_collector\Service\EntityCollectionManager $entityCollectionManager
   * @param \Drupal\Core\Routing\RouteProvider $routeProvider
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityCollectionManager $entityCollectionManager, RouteProvider $routeProvider, AccountInterface $currentUser, CurrentRouteMatch $currentRouteMatch) {
    $this->action = 'remove';
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entityCollectionManager, $routeProvider, $currentUser, $currentRouteMatch);
  }

  /**
   * {@inheritdoc}
   */
  protected function getActionLinkRenderArray(EntityCollectionTypeInterface $entityCollectionType, EntityCollectionInterface $entityCollection, ContentEntityInterface $entity) {
    $field = $this->getBaseField($entityCollectionType, $entity);
    $field['#link']['#attributes']['data-entity-collection'] = $entityCollection->id();
    $field['#link']['#url'] = Url::fromRoute($this->routeName, [
      'entityCollectionId' => $entityCollection->id(),
      'entityId' => $entity->id(),
    ]);

    return $field;
  }

  /**
   * {@inheritdoc}
   */
  protected function getActionLinkTitle(EntityCollectionTypeInterface $entityCollectionType) {
    return $this->t('Remove from :label collection', [':label' => $entityCollectionType->label()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function applyHiddenClass(EntityCollectionTypeInterface $entityCollectionType, EntityCollectionInterface $entityCollection = NULL, ContentEntityInterface $entity) {
    return empty($entityCollection) || !$this->entityCollectionManager->entityExistsInEntityCollection($entityCollection, $entity->id()) ? TRUE: FALSE;
  }

}
