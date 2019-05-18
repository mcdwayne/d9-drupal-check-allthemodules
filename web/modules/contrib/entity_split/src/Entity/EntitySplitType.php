<?php

namespace Drupal\entity_split\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the entity split type entity.
 *
 * @ConfigEntityType(
 *   id = "entity_split_type",
 *   label = @Translation("Entity split type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\entity_split\EntitySplitTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_split\Form\EntitySplitTypeForm",
 *       "edit" = "Drupal\entity_split\Form\EntitySplitTypeForm",
 *       "delete" = "Drupal\entity_split\Form\EntitySplitTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "entity_split_type",
 *   admin_permission = "administer entity split types",
 *   bundle_of = "entity_split",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/entity_split_type/add",
 *     "edit-form" = "/admin/structure/entity_split_type/{entity_split_type}",
 *     "delete-form" = "/admin/structure/entity_split_type/{entity_split_type}/delete",
 *     "collection" = "/admin/structure/entity_split_type"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "entity_type",
 *     "bundle",
 *   }
 * )
 */
class EntitySplitType extends ConfigEntityBundleBase implements EntitySplitTypeInterface {

  /**
   * The entity split type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The entity split type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The master entity type.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The bundle of master entity type.
   *
   * @var string
   */
  protected $bundle;

  /**
   * {@inheritdoc}
   */
  public function getMasterEntityType() {
    return $this->entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setMasterEntityType($entity_type) {
    $this->entity_type = $entity_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMasterBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setMasterBundle($bundle) {
    $this->bundle = $bundle;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function getEntitySplitTypesForEntity(ContentEntityInterface $entity) {
    static $cache = [];

    $bundle_infos = \Drupal::service('entity_type.bundle.info')->getBundleInfo('entity_split');

    if (empty($bundle_infos)) {
      return [];
    }

    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    if (isset($cache[$entity_type][$bundle])) {
      return $cache[$entity_type][$bundle];
    }

    $entity_split_types = array_filter(static::loadMultiple(array_keys($bundle_infos)), function (EntitySplitType $entity_split_type) use ($entity_type, $bundle) {
      return ($entity_split_type->getMasterEntityType() === $entity_type) && ($entity_split_type->getMasterBundle() === $bundle);
    });

    $cache[$entity_type][$bundle] = $entity_split_types;

    return $entity_split_types;
  }

  /**
   * {@inheritdoc}
   */
  public static function getEntitySplitTypesForEntityType($entity_type) {
    static $cache = [];

    $bundle_infos = \Drupal::service('entity_type.bundle.info')->getBundleInfo('entity_split');

    if (empty($bundle_infos)) {
      return [];
    }

    if (isset($cache[$entity_type])) {
      return $cache[$entity_type];
    }

    $entity_split_types = array_filter(static::loadMultiple(array_keys($bundle_infos)), function (EntitySplitType $entity_split_type) use ($entity_type) {
      return $entity_split_type->getMasterEntityType() === $entity_type;
    });

    $cache[$entity_type] = $entity_split_types;

    return $entity_split_types;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatableBundle() {
    static $result = NULL;

    if (!isset($result)) {
      $bundle_infos = \Drupal::service('entity_type.bundle.info')->getBundleInfo('entity_split');
      $result = !empty($bundle_infos[$this->id]['translatable']) && $this->languageManager()->isMultilingual();
    }

    return $result;
  }

}
