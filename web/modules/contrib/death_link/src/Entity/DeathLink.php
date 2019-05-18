<?php

namespace Drupal\death_link\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Url;

/**
 * Defines the death link entity.
 *
 * @ConfigEntityType(
 *   id = "death_link",
 *   label = @Translation("Death Link"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\death_link\DeathLinkListBuilder",
 *     "form" = {
 *       "add" = "Drupal\death_link\Form\DeathLinkForm",
 *       "edit" = "Drupal\death_link\Form\DeathLinkForm",
 *       "delete" = "Drupal\death_link\Form\DeathLinkDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\death_link\DeathLinkHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "death_link",
 *   admin_permission = "administer death link",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "fromUri" = "fromUri",
 *     "toEntityId" = "toEntityId",
 *     "toEntityType" = "toEntityType",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/death_link/{death_link}",
 *     "add-form" = "/admin/structure/death_link/add",
 *     "edit-form" = "/admin/structure/death_link/{death_link}/edit",
 *     "delete-form" = "/admin/structure/death_link/{death_link}/delete",
 *     "collection" = "/admin/structure/death_link/view"
 *   },
 *   field_ui_base_route = "death_link.default"
 * )
 */
class DeathLink extends ConfigEntityBase {

  /**
   * The Redirect ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Redirect label.
   *
   * @var string
   */
  protected $label;

  /**
   * The path to redirect.
   *
   * @var string
   */
  protected $fromUri;

  /**
   * The path to redirect to.
   *
   * @var string
   */
  protected $toUri;

  /**
   * {@inheritdoc}
   */
  public function getFromUri() {
    return $this->fromUri ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getToUri() {
    return $this->toUri ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getToEntity() {
    return $this->deriveEntity($this->getToUri());
  }

  /**
   * {@inheritdoc}
   */
  private function deriveEntity($uri) {
    try {
      // Get the params.
      $params = Url::fromUri('internal:' . $uri)->getRouteParameters();

      // Load entity from the path source.
      $paramKeys = array_keys($params);
      $entityType = array_pop($paramKeys);
      $entity = $this->entityTypeManager()->getStorage($entityType)->load($params[$entityType]);
    }
    catch (\Exception $e) {
      $entity = NULL;
    }
    return $entity;
  }

}
