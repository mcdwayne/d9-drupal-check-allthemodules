<?php

namespace Drupal\elastic_search\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\elastic_search\ValueObject\IdDetails;

/**
 * Defines the Fieldable entity map entity.
 *
 * @ConfigEntityType(
 *   id = "fieldable_entity_map",
 *   label = @Translation("Fieldable entity map"),
 *   handlers = {
 *     "list_builder" = "Drupal\elastic_search\FieldableEntityMapListBuilder",
 *     "form" = {
 *       "add" = "Drupal\elastic_search\Form\FieldableEntityMapForm",
 *       "edit" = "Drupal\elastic_search\Form\FieldableEntityMapForm",
 *       "delete" = "Drupal\elastic_search\Form\FieldableEntityMapDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\elastic_search\FieldableEntityMapHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "fieldable_entity_map",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" =
 *     "/admin/config/search/elastic/fem/{fieldable_entity_map}",
 *     "edit-form" =
 *     "/admin/config/search/elastic/fem/{fieldable_entity_map}/edit",
 *     "delete-form" =
 *     "/admin/config/search/elastic/fem/{fieldable_entity_map}/delete",
 *     "collection" = "/admin/config/search/elastic/fem"
 *   }
 * )
 */
class FieldableEntityMap extends ConfigEntityBase implements FieldableEntityMapInterface {

  /**
   * @var \Drupal\elastic_search\ValueObject\IdDetails
   */
  protected $idDetails;

  /**
   * @return \Drupal\elastic_search\ValueObject\IdDetails
   */
  public function getIdDetails(): IdDetails {
    if ($this->idDetails === NULL) {
      //trigger regeneration of the id details object if it does not exist
      $this->setId($this->getId());
    }
    return $this->idDetails;
  }

  /**
   * @param string $entity
   * @param string $bundle
   *
   * @return string
   */
  public static function getMachineName(string $entity,
                                        string $bundle): string {
    $suffix = empty($bundle) ? $entity : $bundle;
    return $entity . '__' . $suffix;
  }

  /**
   * {@inheritdoc}
   */
  public static function getEntityAndBundle(string $entityId): array {
    $output = [];
    list($output['entity'], $output['bundle']) = explode('__', $entityId);
    return $output;
  }

  /**
   * The Fieldable entity map ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Fieldable entity map label.
   *
   * @var string
   */
  protected $label;

  /**
   * @var bool
   */
  protected $active;

  /**
   * @var mixed[]
   */
  protected $fields = [];

  /**
   * @var bool
   */
  protected $childOnly = FALSE;

  /**
   * @var bool
   */
  protected $dynamicMapping = FALSE;

  /**
   * @deprecated This will be removed in future versions as it is only a switch in the form to turn all object to
   *   simple_reference type as such it does not need to be stored, and could be removed from this entity
   * @var bool
   */
  protected $simpleReference = FALSE;

  /**
   * @var int
   */
  protected $recursionDepth = self::DEFAULT_RECURSION_DEPTH;

  /**
   * The default recursion depth
   */
  CONST DEFAULT_RECURSION_DEPTH = 1;

  /**
   * @return string
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * @param string $id
   */
  public function setId(string $id) {
    $this->id = $id;
    $elements = FieldableEntityMap::getEntityAndBundle($id);
    $this->idDetails = new IdDetails($elements['entity'], $elements['bundle']);
  }

  /**
   * @return string
   */
  public function getLabel(): string {
    return $this->label;
  }

  /**
   * @param string $label
   */
  public function setLabel(string $label) {
    $this->label = $label;
  }

  /**
   * @return bool
   */
  public function isActive(): bool {
    return $this->active;
  }

  /**
   * @param bool $active
   */
  public function setActive(bool $active) {
    $this->active = $active;
  }

  /**
   * @return \mixed[]
   */
  public function getFields(): array {
    return $this->fields;
  }

  /**
   * @param array $fields
   */
  public function setFields(array $fields) {
    $this->fields = $fields;
  }

  /**
   * @return bool
   */
  public function isChildOnly(): bool {
    return $this->childOnly;
  }

  /**
   * @param bool $childOnly
   */
  public function setChildOnly(bool $childOnly) {
    $this->childOnly = $childOnly;
  }

  /**
   * @inheritDoc
   */
  public function isSimpleReference(): bool {
    return $this->simpleReference;
  }

  /**
   * @inheritDoc
   */
  public function setSimpleReference(bool $state = TRUE) {
    $this->simpleReference = $state;
  }

  /**
   * @inheritDoc
   */
  public function hasDynamicMapping(): bool {
    return $this->dynamicMapping;
  }

  /**
   * @inheritDoc
   */
  public function setDynamicMapping(bool $state = TRUE) {
    $this->dynamicMapping = $state;
  }

  /**
   * @inheritDoc
   */
  public function setRecursionDepth(int $depth) {
    $this->recursionDepth = $depth;
  }

  /**
   * @inheritDoc
   */
  public function getRecursionDepth(): int {
    return $this->recursionDepth;
  }

}
