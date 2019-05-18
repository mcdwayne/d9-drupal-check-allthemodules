<?php

namespace Drupal\block_theme_sync\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the theme mapping entity.
 *
 * @ConfigEntityType(
 *   id = "theme_mapping",
 *   label = @Translation("Theme mapping"),
 *   handlers = {
 *     "list_builder" = "Drupal\block_theme_sync\ThemeMappingListBuilder",
 *     "form" = {
 *       "add" = "Drupal\block_theme_sync\Form\ThemeMappingForm",
 *       "edit" = "Drupal\block_theme_sync\Form\ThemeMappingForm",
 *       "delete" = "Drupal\block_theme_sync\Form\ThemeMappingDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\block_theme_sync\ThemeMappingHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "theme_mapping",
 *   admin_permission = "administer theme mappings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/block/theme_mapping/{theme_mapping}",
 *     "add-form" = "/admin/structure/block/theme_mapping/add",
 *     "edit-form" = "/admin/structure/block/theme_mapping/{theme_mapping}/edit",
 *     "delete-form" = "/admin/structure/block/theme_mapping/{theme_mapping}/delete",
 *     "collection" = "/admin/structure/block/theme_mapping"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "source",
 *     "destination",
 *     "region_mapping",
 *   }
 * )
 */
class ThemeMapping extends ConfigEntityBase implements ThemeMappingInterface {

  /**
   * The theme mapping ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The theme mapping label.
   *
   * @var string
   */
  protected $label;

  /**
   * The source theme.
   *
   * @var string
   */
  protected $source;

  /**
   * The destination theme.
   *
   * @var string
   */
  protected $destination;

  /**
   * The region mapping.
   *
   * @var array
   */
  protected $region_mapping;

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function setSource($source) {
    $this->source = $source;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDestination() {
    return $this->destination;
  }

  /**
   * {@inheritdoc}
   */
  public function setDestination($destination) {
    $this->destination = $destination;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegionMapping() {
    return $this->region_mapping;
  }

  /**
   * {@inheritdoc}
   */
  public function setRegionMapping(array $region_mapping) {
    $this->region_mapping = $region_mapping;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->addDependency('theme', $this->source);
    $this->addDependency('theme', $this->destination);

    return $this;
  }

}
