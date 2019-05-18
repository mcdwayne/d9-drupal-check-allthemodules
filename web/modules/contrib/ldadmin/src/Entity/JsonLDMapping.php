<?php

namespace Drupal\ldadmin\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the JSON-LD Mapping entity.
 *
 * @ConfigEntityType(
 *   id = "jsonld_mapping",
 *   label = @Translation("JSON-LD Mapping"),
 *   handlers = {
 *     "list_builder" = "Drupal\ldadmin\JsonLDMappingListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ldadmin\Form\JsonLDMappingForm",
 *       "edit" = "Drupal\ldadmin\Form\JsonLDMappingForm",
 *       "delete" = "Drupal\ldadmin\Form\JsonLDMappingDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ldadmin\JsonLDMappingHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "jsonld_mapping",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/jsonld_mapping/{jsonld_mapping}",
 *     "add-form" = "/admin/structure/jsonld_mapping/add",
 *     "edit-form" = "/admin/structure/jsonld_mapping/{jsonld_mapping}/edit",
 *     "delete-form" = "/admin/structure/jsonld_mapping/{jsonld_mapping}/delete",
 *     "collection" = "/admin/structure/jsonld_mapping"
 *   }
 * )
 */
class JsonLDMapping extends ConfigEntityBase implements JsonLDMappingInterface {

  /**
   * The JSON-LD Mapping ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The JSON-LD Mapping description label.
   *
   * @var string
   */
  protected $label;

  /**
   * The JSON-LD Mapping NID.
   *
   * @var string
   */
  protected $nid;

  /**
   * The JSON-LD Mapping JSON data.
   *
   * @var string
   */
  protected $json;

  /**
   * Get Nid.
   */
  public function getNid() {
    return $this->get('nid');
  }

  /**
   * Get Json.
   */
  public function getJson() {
    return $this->get('json');
  }

  /**
   * Set Nid.
   */
  public function setNid($nid) {
    $this->set('nid', $nid);
  }

  /**
   * Set Json.
   */
  public function setJson($json) {
    $this->set('json', $json);
  }

}
