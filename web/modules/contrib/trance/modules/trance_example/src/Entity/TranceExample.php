<?php

namespace Drupal\trance_example\Entity;

use Drupal\trance\Trance;

/**
 * Defines the trance_example entity.
 *
 * @ingroup trance_example
 *
 * @ContentEntityType(
 *   id = "trance_example",
 *   label = @Translation("TranceExample"),
 *   bundle_label = @Translation("trance_example type"),
 *   handlers = {
 *     "storage" = "Drupal\trance\TranceStorage",
 *     "storage_schema" = "Drupal\trance\TranceStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\trance\TranceListBuilder",
 *     "views_data" = "Drupal\trance\TranceViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\trance_example\Form\TranceExampleForm",
 *       "add" = "Drupal\trance_example\Form\TranceExampleForm",
 *       "edit" = "Drupal\trance_example\Form\TranceExampleForm",
 *       "delete" = "Drupal\trance\Form\TranceDeleteForm",
 *     },
 *     "access" = "Drupal\trance\Access\TranceAccessControlHandler",
 *   },
 *   base_table = "trance_example",
 *   data_table = "trance_example_field_data",
 *   revision_table = "trance_example_revision",
 *   revision_data_table = "trance_example_field_revision",
 *   admin_permission = "administer trance_example entities",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status"
 *   },
 *   links = {
 *     "collection" = "/admin/content/trance_example",
 *     "canonical" = "/admin/content/trance_example/{trance_example}",
 *     "edit-form" = "/admin/content/trance_example/{trance_example}/edit",
 *     "delete-form" = "/admin/content/trance_example/{trance_example}/delete"
 *   },
 *   bundle_entity_type = "trance_example_type",
 *   field_ui_base_route = "entity.trance_example_type.edit_form"
 * )
 */
class TranceExample extends Trance {

}
