<?php

namespace Drupal\icecat\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Icecat mapping config entity.
 *
 * @ConfigEntityType(
 *   id ="icecat_mapping",
 *   label = @Translation("Icecat Mapping"),
 *   handlers = {
 *     "list_builder" = "Drupal\icecat\IcecatMappingListBuilder",
 *     "form" = {
 *       "default" = "Drupal\icecat\IcecatMappingForm",
 *       "add" = "Drupal\icecat\IcecatMappingForm",
 *       "edit" = "Drupal\icecat\IcecatMappingForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "icecat_mapping",
 *   admin_permission = "manage icecat mappings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/icecat/mappings/add",
 *     "delete-form" = "/admin/structure/icecat/mappings/{icecat_mapping}/delete",
 *     "edit-form" = "/admin/structure/icecat/mappings/{icecat_mapping}",
 *     "collection" = "/admin/structure/icecat/mappings",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "example_ean",
 *     "entity_type",
 *     "entity_type_bundle",
 *     "data_input_field",
 *   }
 * )
 */
class IcecatMapping extends ConfigEntityBase implements IcecatMappingInterface {

  /**
   * Constructs a Icecat mapping config.
   *
   * @param array $values
   *   The values to store.
   */
  public function __construct(array $values) {
    parent::__construct($values, 'icecat_mapping');
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingEntityType() {
    return $this->get('entity_type');
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingEntityBundle() {
    return $this->get('entity_type_bundle');
  }

  /**
   * {@inheritdoc}
   */
  public function getDataInputField() {
    return $this->get('data_input_field');
  }

  /**
   * {@inheritdoc}
   */
  public function getExampleEans() {
    return $this->get('example_ean');
  }

  /**
   * Gets a list of ean codes.
   */
  public function getExampleEanList() {
    return explode(',', preg_replace('/\s+/', '', $this->get('example_ean')));
  }

}
