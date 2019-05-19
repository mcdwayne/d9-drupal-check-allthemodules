<?php

namespace Drupal\user_request\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Configuration entity to define response types.
 *
 * @ConfigEntityType(
 *   id = "user_request_response_type",
 *   label = @Translation("Response type"),
 *   label_collection = @Translation("Response types"),
 *   label_plural = @Translation("response types"),
 *   admin_permission = "administer user_request_response_type",
 *   bundle_of = "user_request_response",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *   },
 *   handlers = {
 *     "access" = "Drupal\entity\EntityAccessControlHandler",
 *     "list_builder" = "Drupal\entity_extra\Controller\ConfigEntityListBuilder",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "local_action_provider" = {
 *       "Drupal\entity\Menu\EntityCollectionLocalActionProvider",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\user_request\Form\ResponseTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/user-request/response-type/{user_request_response_type}",
 *     "collection" = "/admin/structure/user-request/response-type",
 *     "add-form" = "/admin/structure/user-request/response-type/add",
 *     "edit-form" = "/admin/structure/user-request/response-type/{user_request_response_type}/edit",
 *     "delete-form" = "/admin/structure/user-request/response-type/{user_request_response_type}/delete",
 *   }
 * )
 */
class ResponseType extends ConfigEntityBundleBase implements ResponseTypeInterface {

  /**
   * The bundle ID.
   *
   * @var string
   */
  protected $id;

}
