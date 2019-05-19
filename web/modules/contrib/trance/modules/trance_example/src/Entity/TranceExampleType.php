<?php

namespace Drupal\trance_example\Entity;

use Drupal\trance\TranceType;

/**
 * Defines the trance_example type entity.
 *
 * @ConfigEntityType(
 *   id = "trance_example_type",
 *   label = @Translation("trance_example type"),
 *   handlers = {
 *     "list_builder" = "Drupal\trance\TranceTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\trance\Form\TranceTypeForm",
 *       "edit" = "Drupal\trance\Form\TranceTypeForm",
 *       "delete" = "Drupal\trance\Form\TranceTypeDeleteForm"
 *     }
 *   },
 *   config_prefix = "trance_example_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "trance_example",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/trance_example_type/{trance_example_type}",
 *     "edit-form" = "/admin/structure/trance_example_type/{trance_example_type}/edit",
 *     "delete-form" = "/admin/structure/trance_example_type/{trance_example_type}/delete",
 *     "collection" = "/admin/structure/trance_example_type"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "help"
 *   }
 * )
 */
class TranceExampleType extends TranceType {

}
