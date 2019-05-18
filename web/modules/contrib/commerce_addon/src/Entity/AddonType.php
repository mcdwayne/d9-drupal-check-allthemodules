<?php

namespace Drupal\commerce_addon\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityBase;

/**
 * Defines the order type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_addon_type",
 *   label = @Translation("Addon type"),
 *   label_collection = @Translation("Addon types"),
 *   label_singular = @Translation("addon type"),
 *   label_plural = @Translation("addon types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count addon type",
 *     plural = "@count addon types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_addon\Form\AddonTypeForm",
 *       "edit" = "Drupal\commerce_addon\Form\AddonTypeForm",
 *       "delete" = "Drupal\commerce\Form\CommerceBundleEntityDeleteFormBase"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\commerce_addon\AddonTypeListBuilder",
 *   },
 *   admin_permission = "administer commerce_addon_type",
 *   config_prefix = "commerce_addon_type",
 *   bundle_of = "commerce_addon",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/addons-types/add",
 *     "edit-form" = "/admin/commerce/config/addons-types/{commerce_addon_type}/edit",
 *     "delete-form" = "/admin/commerce/config/addons-types/{commerce_addon_type}/delete",
 *     "collection" = "/admin/commerce/config/addons-types"
 *   }
 * )
 */
class AddonType extends CommerceBundleEntityBase implements AddonTypeInterface {

}
