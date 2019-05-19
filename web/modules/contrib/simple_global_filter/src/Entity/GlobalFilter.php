<?php

namespace Drupal\simple_global_filter\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Global filter entity.
 *
 * @ConfigEntityType(
 *   id = "global_filter",
 *   label = @Translation("Global filter"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\simple_global_filter\GlobalFilterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\simple_global_filter\Form\GlobalFilterForm",
 *       "edit" = "Drupal\simple_global_filter\Form\GlobalFilterForm",
 *       "delete" = "Drupal\simple_global_filter\Form\GlobalFilterDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\simple_global_filter\GlobalFilterHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "global_filter",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/global_filter/{global_filter}",
 *     "add-form" = "/admin/structure/global_filter/add",
 *     "edit-form" = "/admin/structure/global_filter/{global_filter}/edit",
 *     "delete-form" = "/admin/structure/global_filter/{global_filter}/delete",
 *     "collection" = "/admin/structure/global_filter"
 *   }
 * )
 */
class GlobalFilter extends ConfigEntityBase implements GlobalFilterInterface {

  /**
   * The Global filter ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Global filter label.
   *
   * @var string
   */
  protected $label;

  /**
   * The alias field.
   * It is the field that stores the information about the alias
   */
  protected $alias_field;

  /**
   * The global filter related taxonomy vocabulary
   * @var string
   */
  protected $vocabulary_name;

  /**
   * If there has not been selected any value yet, return this value.
   * @var type
   */
  protected $default_value;

  /**
   * {@inheritdoc}
   */
  public function getVocabulary() {
    return $this->vocabulary_name;
  }

  /**
   * {@inheritdoc}
   */
  public function setVocabulary($vocabulary_name) {
    $this->vocabulary_name = $vocabulary_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return $this->default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasField() {
    return $this->alias_field;
  }
}
