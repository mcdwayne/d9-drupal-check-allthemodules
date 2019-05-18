<?php

namespace Drupal\erf\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Registration type entity.
 *
 * @ConfigEntityType(
 *   id = "registration_type",
 *   label = @Translation("Registration type"),
 *   label_collection = @Translation("Registration types"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\erf\RegistrationTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\erf\Form\RegistrationTypeForm",
 *       "edit" = "Drupal\erf\Form\RegistrationTypeForm",
 *       "delete" = "Drupal\erf\Form\RegistrationTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\erf\RegistrationTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "registration_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "registration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/registrations/registration_types/{registration_type}",
 *     "add-form" = "/admin/registrations/registration_types/add",
 *     "edit-form" = "/admin/registrations/registration_types/{registration_type}/edit",
 *     "delete-form" = "/admin/registrations/registration_types/{registration_type}/delete",
 *     "collection" = "/admin/registrations/registration_types"
 *   }
 * )
 */
class RegistrationType extends ConfigEntityBundleBase implements RegistrationTypeInterface {

  /**
   * The Registration type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Registration type label.
   *
   * @var string
   */
  protected $label;

}
