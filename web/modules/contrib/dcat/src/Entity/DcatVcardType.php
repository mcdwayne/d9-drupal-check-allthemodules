<?php

namespace Drupal\dcat\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the vCard type entity.
 *
 * @ConfigEntityType(
 *   id = "dcat_vcard_type",
 *   label = @Translation("vCard type"),
 *   handlers = {
 *     "list_builder" = "Drupal\dcat\DcatVcardTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dcat\Form\DcatVcardTypeForm",
 *       "edit" = "Drupal\dcat\Form\DcatVcardTypeForm",
 *       "delete" = "Drupal\dcat\Form\DcatVcardTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\dcat\DcatVcardTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "dcat_vcard_type",
 *   admin_permission = "administer vcard enity types",
 *   bundle_of = "dcat_vcard",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/dcat/settings/dcat_vcard_type/{dcat_vcard_type}",
 *     "add-form" = "/admin/structure/dcat/settings/dcat_vcard_type/add",
 *     "edit-form" = "/admin/structure/dcat/settings/dcat_vcard_type/{dcat_vcard_type}/edit",
 *     "delete-form" = "/admin/structure/dcat/settings/dcat_vcard_type/{dcat_vcard_type}/delete",
 *     "collection" = "/admin/structure/dcat/settings/dcat_vcard_type"
 *   }
 * )
 */
class DcatVcardType extends ConfigEntityBundleBase implements DcatVcardTypeInterface {

  /**
   * The vCard type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The vCard type label.
   *
   * @var string
   */
  protected $label;

}
