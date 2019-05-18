<?php

namespace Drupal\elastic_search\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the Elastic index entity.
 *
 * @ConfigEntityType(
 *   id = "elastic_index",
 *   label = @Translation("Elastic index"),
 *   handlers = {
 *     "list_builder" = "Drupal\elastic_search\ElasticIndexListBuilder",
 *     "form" = {
 *       "add" = "Drupal\elastic_search\Form\ElasticIndexForm",
 *       "edit" = "Drupal\elastic_search\Form\ElasticIndexForm",
 *       "delete" = "Drupal\elastic_search\Form\ElasticIndexDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\elastic_search\ElasticIndexHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "elastic_index",
 *   admin_permission = "administer advanced elasticsearch",
 *   entity_keys = {
 *     "id" = "id",
 *     "indexId" = "indexId",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" =
 *   "/admin/config/search/elastic/index/{elastic_index}",
 *     "add-form" = "/admin/config/search/elastic/index/add",
 *     "edit-form" =
 *   "/admin/config/search/elastic/index/{elastic_index}/edit",
 *     "delete-form" =
 *   "/admin/config/search/elastic/index/{elastic_index}/delete",
 *     "collection" = "/admin/config/search/elastic/index"
 *   }
 * )
 */
class ElasticIndex extends ConfigEntityBase implements ElasticIndexInterface {

  /**
   * The Elastic index ID.
   *
   * @var string
   */
  protected $id;

  /**
   * @var string
   */
  protected $indexId = '';

  /**
   * @var string
   */
  protected $indexLanguage = '';

  /**
   * @var string
   */
  protected $separator = '_';

  /**
   * @var bool
   */
  protected $needsUpdate = FALSE;

  /**
   * @var string
   */
  protected $mappingEntityId = '';

  /**
   *
   */
  const FIXED_ENTITY_BUNDLE_SEPERATOR = '__';

  /**
   * @param string $entityType
   * @param string $bundle
   * @param string $language
   * @param string $prefix
   * @param string $separator
   *
   * @return mixed
   */
  public static function buildIndexName(string $entityType,
                                        string $bundle,
                                        string $language,
                                        string $prefix = '',
                                        string $separator = '_'): string {
    return static::buildNameWithIndexId($entityType . self::FIXED_ENTITY_BUNDLE_SEPERATOR . $bundle,
                                        $language,
                                        $prefix,
                                        $separator);
  }

  /**
   * Pass in an entity and get what the index name would be
   * This does NOT guarantee that this index exists, it just tells you what the id would be
   * Use loadFromReferencingEntity to get the entity or null if you need the index itself
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return mixed|string
   */
  public static function getIndexNameFromEntity(EntityInterface $entity) {
    $type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $lang = $entity->language()->getId();
    //We do not include a prefix as these are dynamic and not saved with the index
    return self::buildIndexName($type, $bundle, $lang);
  }

  public static function loadFromReferencingEntity(EntityInterface $entity) {
    $indexName = self::getIndexNameFromEntity($entity);
    return ElasticIndex::load($indexName);

  }

  /**
   * @param string $indexId
   * @param string $language
   * @param string $prefix
   * @param string $separator
   *
   * @return string
   */
  protected static function buildNameWithIndexId(string $indexId,
                                                 string $language,
                                                 string $prefix = '',
                                                 string $separator = '_'): string {
    if ($prefix !== '') {
      $prefix .= $separator;
    }
    return $prefix . $indexId . $separator . $language;
  }

  /**
   * @return string
   * @deprecated you should call getCanonicalName instead. TODO - will be removed in version 2.0
   */
  public function getIndexName(): string {
    return $this->getCanonicalName();
  }

  /**
   * Gets the index prefix from the config and returns it.
   *
   * @return string
   */
  public function getCanonicalName($prefix = NULL): string {
    //TODO - is there a way to avoid using \Drupal::config here?
    if ($prefix === NULL) {
      $config = \Drupal::config('elastic_search.server');
      $prefix = $config->get('index_prefix') ?? '';
    }
    return static::buildNameWithIndexId($this->indexId, $this->indexLanguage, $prefix, $this->separator);
  }

  /**
   * @param string $separator
   */
  public function setSeparator(string $separator) {
    $this->separator = $separator;
  }

  /**
   * @return string
   */
  public function getSeparator(): string {
    return $this->separator;
  }

  /**
   * @param string $indexId
   */
  public function setIndexId(string $indexId) {
    $this->indexId = $indexId;
  }

  /**
   * @return string
   */
  public function getIndexId(): string {
    return $this->indexId;
  }

  /**
   * @param string $indexLanguage
   */
  public function setIndexLanguage(string $indexLanguage) {
    $this->indexLanguage = $indexLanguage;
  }

  /**
   * @return string
   */
  public function getIndexLanguage(): string {
    return $this->indexLanguage;
  }

  /**
   * @return bool
   */
  public function needsUpdate(): bool {
    return $this->needsUpdate;
  }

  /**
   * @param bool $needsUpdate
   */
  public function setNeedsUpdate(bool $needsUpdate = TRUE) {
    $this->needsUpdate = $needsUpdate;
  }

  /**
   * @return string
   */
  public function getMappingEntityId(): string {
    return $this->mappingEntityId;
  }

  /**
   * @param string $mappingEntityId
   */
  public function setMappingEntityId(string $mappingEntityId) {
    $this->mappingEntityId = $mappingEntityId;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $this->id = $this->getCanonicalName('');
    $return = parent::save();
    $this->trustedData = FALSE;
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a,
                              ConfigEntityInterface $b) {
    $a_weight = $a->weight ?? 0;
    $b_weight = $b->weight ?? 0;
    if ($a_weight === $b_weight) {
      $a_label = $a->getIndexName();
      $b_label = $b->getIndexName();
      return strnatcasecmp($a_label, $b_label);
    }
    return ($a_weight < $b_weight) ? -1 : 1;
  }

}
