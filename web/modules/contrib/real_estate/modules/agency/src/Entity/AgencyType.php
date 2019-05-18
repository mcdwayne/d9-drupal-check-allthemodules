<?php

namespace Drupal\real_estate_agency\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Agency type entity.
 *
 * @ConfigEntityType(
 *   id = "real_estate_agency_type",
 *   label = @Translation("Agency type"),
 *   handlers = {
 *     "list_builder" = "Drupal\real_estate_agency\AgencyTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\real_estate_agency\Form\AgencyTypeForm",
 *       "edit" = "Drupal\real_estate_agency\Form\AgencyTypeForm",
 *       "delete" = "Drupal\real_estate_agency\Form\AgencyTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\real_estate_agency\AgencyTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "real_estate_agency_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "real_estate_agency",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/real-estate/config/agency-type/{real_estate_agency_type}",
 *     "add-form" = "/admin/real-estate/config/agency-type/add",
 *     "edit-form" = "/admin/real-estate/config/agency-type/{real_estate_agency_type}/edit",
 *     "delete-form" = "/admin/real-estate/config/agency-type/{real_estate_agency_type}/delete",
 *     "collection" = "/admin/real-estate/config/agency-types"
 *   }
 * )
 */
class AgencyType extends ConfigEntityBundleBase implements AgencyTypeInterface {

  /**
   * The Agency type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Agency type label.
   *
   * @var string
   */
  protected $label;

}
