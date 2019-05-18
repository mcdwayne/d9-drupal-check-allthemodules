<?php

namespace Drupal\gclient\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the entity class.
 *
 * @ConfigEntityType(
 *   id = "gclient_google_project",
 *   label = @Translation("Google Project"),
 *   label_collection = @Translation("Google Projects"),
 *   label_singular = @Translation("google project"),
 *   label_plural = @Translation("google projects"),
 *   label_count = @PluralTranslation(
 *     singular = "@count google project",
 *     plural = "@count google projects",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\gclient\Form\GoogleProjectForm",
 *       "edit" = "Drupal\gclient\Form\GoogleProjectForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\gclient\GoogleProjectListBuilder",
 *   },
 *   admin_permission = "administer gclient_google_project",
 *   config_prefix = "project",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "project_id",
 *     "project_number"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/gclient/projects/add",
 *     "edit-form" = "/admin/config/gclient/projects/{gclient_google_project}",
 *     "delete-form" = "/admin/config/gclient/projects/{gclient_google_project}/delete",
 *     "collection" = "/admin/config/gclient/projects"
 *   }
 * )
 */
class GoogleProject extends ConfigEntityBase implements GoogleProjectInterface {

}
