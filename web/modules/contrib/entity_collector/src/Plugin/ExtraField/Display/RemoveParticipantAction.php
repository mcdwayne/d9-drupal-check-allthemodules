<?php
/**
 * Created by PhpStorm.
 * User: leonvanderhaas
 * Date: 17/12/2018
 * Time: 10:59
 */

namespace Drupal\entity_collector\Plugin\ExtraField\Display;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\entity_collector\Entity\EntityCollectionInterface;
use Drupal\entity_collector\Entity\EntityCollectionTypeInterface;
use Drupal\entity_collector\Service\EntityCollectionManager;

/**
 * Class RemoveParticipantAction
 *
 * @package Drupal\entity_collector\Plugin\ExtraField\Display
 *
 * @ExtraFieldDisplay(
 *   id = "remove_participant_from_entity_collection",
 *   label = @Translation("Remove participant from collection"),
 *   bundles = {
 *        "entity_collection.*"
 *   }
 * )
 */
class RemoveParticipantAction extends EntityCollectionParticipantActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityCollectionManager $entityCollectionManager, RouteProvider $routeProvider, AccountInterface $currentUser, CurrentRouteMatch $currentRouteMatch) {
    $this->action = 'remove';
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entityCollectionManager, $routeProvider, $currentUser, $currentRouteMatch);
  }

  /**
   * {@inheritdoc}
   */
  protected function getActionLinkRenderArray(EntityCollectionTypeInterface $entityCollectionType, EntityCollectionInterface $entityCollection) {
    $field = $this->getBaseField($entityCollectionType);
    $field['#link']['#attributes']['data-entity-collection'] = $entityCollection->id();
    $field['#link']['#url'] = Url::fromRoute($this->routeName, ['entityCollectionId' => $entityCollection->id()]);

    return $field;
  }

  /**
   * {@inheritdoc}
   */
  protected function getActionLinkTitle(EntityCollectionTypeInterface $entityCollectionType) {
    return $this->t('Remove participant from :label collection', [':label' => $entityCollectionType->label()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function applyHiddenClass(EntityCollectionInterface $entityCollection = NULL, AccountInterface $user) {
    return empty($entityCollection) || !$this->entityCollectionManager->isValidCollectionForUser($entityCollection, $user);
  }
}