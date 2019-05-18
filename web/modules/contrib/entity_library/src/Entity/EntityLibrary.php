<?php

namespace Drupal\entity_library\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines an entity library entity class.
 *
 * @ConfigEntityType(
 *   id = "entity_library",
 *   label = @Translation("Entity library"),
 *   label_singular = @Translation("entity library"),
 *   label_plural = @Translation("entity libraries"),
 *   label_count = @PluralTranslation(
 *     singular = "@count entity library",
 *     plural = "@count entity libraries"
 *   ),
 *   admin_permission = "administer entity libraries",
 *   handlers = {
 *     "list_builder" = "Drupal\entity_library\EntityLibraryListBuilder",
 *     "form" = {
 *       "default" = "Drupal\entity_library\Form\EntityLibraryForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider"
 *     },
 *   },
 *   links = {
 *     "add-form" = "/admin/config/system/entity-library/add",
 *     "edit-form" = "/admin/config/system/entity-library/{entity_library}/edit",
 *     "delete-form" = "/admin/config/system/entity-library/{entity_library}/delete",
 *     "collection" = "/admin/config/system/entity-library"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "library_info",
 *     "conditions"
 *   }
 * )
 */
class EntityLibrary extends ConfigEntityBase implements EntityLibraryInterface {

  /**
   * The ID of the library.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the library.
   *
   * @var string
   */
  protected $label;

  /**
   * The description of the library.
   *
   * @var string
   */
  protected $description;

  /**
   * The definition of library.
   *
   * @var string
   */
  protected $library_info;

  /**
   * The condition of library.
   *
   * @var array
   */
  protected $conditions = [];

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraryInfo() {
    return $this->library_info;
  }

  /**
   * {@inheritdoc}
   */
  public function setLibraryInfo(string $library_info) {
    $this->library_info = $library_info;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditions() {
    return $this->conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function setConditions(array $conditions) {
    $this->conditions = $conditions;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    self::clearCachedLibraryDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    self::clearCachedLibraryDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function clearCachedLibraryDefinitions() {
    \Drupal::service('library.discovery')->clearCachedDefinitions();
  }

}
