<?php

namespace Drupal\pathalias_extend\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\pathalias_extend\PathaliasExtendSuffixInterface;

/**
 * Defines the Pathalias Extend Suffix entity.
 *
 * @ConfigEntityType(
 *   id = "pathalias_extend_suffix",
 *   label = @Translation("Pathalias Extend Suffix"),
 *   label_singular = @Translation("Pathalias Extend Suffix"),
 *   label_plural = @Translation("Pathalias Extend Suffixes"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Pathalias Extend Suffix",
 *     plural = "@count Pathalias Extend Suffixes"
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\pathalias_extend\Controller\SuffixListBuilder",
 *     "form" = {
 *       "add" = "Drupal\pathalias_extend\Form\SuffixForm",
 *       "edit" = "Drupal\pathalias_extend\Form\SuffixForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *   },
 *   translatable = TRUE,
 *   config_prefix = "suffix",
 *   admin_permission = "configure pathalias_extend",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *   },
 *   links = {
 *     "collection" = "/admin/config/search/path/extend",
 *     "edit-form" = "/admin/config/search/path/extend/{pathalias_extend_suffix}",
 *     "delete-form" = "/admin/config/search/path/extend/{pathalias_extend_suffix}/delete",
 *   }
 * )
 */
class PathaliasExtendSuffix extends ConfigEntityBase implements PathAliasExtendSuffixInterface {

  /**
   * The entity's ID.
   *
   * @var string
   */
  public $id;

  /**
   * The entity's label.
   *
   * @var string
   */
  public $label = '';

  /**
   * Target entity type id.
   *
   * @var string
   */
  protected $target_entity_type_id = '';

  /**
   * Target bundle id.
   *
   * @var string
   */
  protected $target_bundle_id = '';

  /**
   * Suffix pattern.
   *
   * @var string
   */
  protected $pattern = '';

  /**
   * Whether to create an alias for the suffix, if missing.
   *
   * @var bool
   */
  protected $create_alias = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId(): string {
    return $this->target_entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetEntityTypeId(string $entity_type_id) {
    $this->target_entity_type_id = $entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundleId(): string {
    return $this->target_bundle_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetBundleId(string $bundle_id) {
    $this->target_bundle_id = $bundle_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPattern(): string {
    return $this->pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setPattern(string $pattern) {
    $this->pattern = $pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreateAlias(): bool {
    return $this->create_alias;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreateAlias(bool $create_alias) {
    $this->create_alias = $create_alias;
  }

}
