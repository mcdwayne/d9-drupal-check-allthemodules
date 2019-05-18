<?php

namespace Drupal\colours\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the colours entity.
 *
 * @ConfigEntityType(
 *   id = "colours",
 *   label = @Translation("Colours"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\colours\ColoursListBuilder",
 *     "form" = {
 *       "add" = "Drupal\colours\Form\ColoursAddForm",
 *       "edit" = "Drupal\colours\Form\ColoursEditForm",
 *       "delete" = "Drupal\colours\Form\ColoursDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\colours\ColoursHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "colours",
 *   admin_permission = "administer colours",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/colours/{colours}",
 *     "add-form" = "/admin/structure/colours/add",
 *     "edit-form" = "/admin/structure/colours/{colours}/edit",
 *     "delete-form" = "/admin/structure/colours/{colours}/delete",
 *     "collection" = "/admin/structure/colours"
 *   }
 * )
 */
class Colours extends ConfigEntityBase implements ColoursInterface {

  /**
   * The Colours ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Colours label.
   *
   * @var string
   */
  protected $label;
  
  /**
   * The colourset mappings.
   * 
   *   An array of colourset mappings. Each colourset mapping array
   *   contains the following keys:
   *   - colour_css_selector
   *   - colour_title
   *   - colour_background
   *   - colour_foreground
   * 
   * @var array
   */
  protected $colourset_mapping = [];

  /**
   * @var array
   */
  protected $keyedColoursetMappings;
  
  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type_id = 'colours') {
    parent::__construct($values, $entity_type_id);
  }
  
  /**
   * {@inheritdoc}
   */
  public function hasColoursetMappings() {
    $mappings = $this->getKeyedColoursetMappings();
    return !empty($mappings);
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyedColoursetMappings() {
    if (!$this->keyedColoursetMappings) {
      $this->keyedColoursetMappings = [];
      foreach ($this->colourset_mappings as $mapping) {
        if (!static::isEmptyImageStyleMapping($mapping)) {
          $this->keyedImageStyleMappings[$mapping['colour_css_selector']] = $mapping;
        }
      }
    }
    return $this->keyedColoursetMappings;
  }

  /**
   * {@inheritdoc}
   */
  public function getColoursetMappings() {
    return $this->colourset_mapping;
  }

}
