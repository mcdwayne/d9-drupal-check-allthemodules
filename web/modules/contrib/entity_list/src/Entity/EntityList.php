<?php

namespace Drupal\entity_list\Entity;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the Entity list entity.
 *
 * @ConfigEntityType(
 *   id = "entity_list",
 *   label = @Translation("Entity list"),
 *   handlers = {
 *     "view_builder" = "Drupal\entity_list\EntityListViewBuilder",
 *     "list_builder" = "Drupal\entity_list\EntityListListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_list\Form\EntityListForm",
 *       "edit" = "Drupal\entity_list\Form\EntityListForm",
 *       "delete" = "Drupal\entity_list\Form\EntityListDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\entity_list\EntityListHtmlRouteProvider",
 *     },
 *     "access": "Drupal\entity_list\Access\EntityListAccessControlHandler"
 *   },
 *   config_prefix = "entity_list",
 *   admin_permission = "administer entity list",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/entity_list/{entity_list}",
 *     "add-form" = "/admin/structure/entity_list/add",
 *     "edit-form" = "/admin/structure/entity_list/{entity_list}/edit",
 *     "delete-form" = "/admin/structure/entity_list/{entity_list}/delete",
 *     "collection" = "/admin/structure/entity_list"
 *   }
 * )
 */
class EntityList extends ConfigEntityBase implements EntityListInterface {

  /**
   * The Entity list ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Entity list label.
   *
   * @var string
   */
  protected $label;

  protected $host;

  /**
   * {@inheritdoc}
   */
  public function getEntityListDisplayPluginId($default = '') {
    return $this->get('display')['plugin'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityListDisplayPlugin($default = '') {
    /** @var \Drupal\entity_list\Plugin\EntityListDisplayManager $entity_list_display_manager */
    $entity_list_display_manager = \Drupal::service('plugin.manager.entity_list_display');
    try {
      return $entity_list_display_manager->createInstance($this->getEntityListDisplayPluginId($default), [
        'entity' => $this,
        'settings' => $this->get('display'),
      ]);
    }
    catch (PluginException $e) {
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityListQueryPluginId($default = '') {
    return $this->get('query')['plugin'] ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityListQueryPlugin($default = '') {
    /** @var \Drupal\entity_list\Plugin\EntityListDisplayManager $entity_list_display_manager */
    $entity_list_display_manager = \Drupal::service('plugin.manager.entity_list_query');
    try {
      return $entity_list_display_manager->createInstance($this->getEntityListQueryPluginId($default), [
        'entity' => $this,
        'settings' => $this->get('query'),
      ]);
    }
    catch (PluginException $e) {
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setHost(EntityInterface $host) {
    $this->host = $host;
  }

  /**
   * {@inheritdoc}
   */
  public function getHost() {
    return $this->host;
  }

}
