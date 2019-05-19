<?php

namespace Drupal\user_request\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\user_request\Entity\Request;

/**
 * Response entity.
 *
 * @ContentEntityType(
 *   id = "user_request_response",
 *   label = @Translation("Response"),
 *   label_plural = @Translation("responses"),
 *   label_collection = @Translation("Responses"),
 *   base_table = "user_request_response",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.user_request_response_type.edit_form",
 *   bundle_entity_type = "user_request_response_type",
 *   bundle_label = @Translation("Response type"),
 *   admin_permission = "administer user_request_response",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "owner" = "owner",
 *     "created" = "created",
 *     "changed" = "changed",
 *     "uuid" = "uuid"
 *   },
 *   handlers = {
 *     "access" = "Drupal\user_request\Access\ResponseAccessControlHandler",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\entity_extra\Controller\ViewsEntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "permission_provider" = "Drupal\user_request\Access\ResponsePermissionProvider",
 *     "local_action_provider" = {
 *       "Drupal\entity\Menu\EntityCollectionLocalActionProvider",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\user_request\Routing\ResponseHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\user_request\Form\ResponseForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *   },
 *   links = {
 *     "canonical" = "/user-request/response/{user_request_response}",
 *     "add-form" = "/user-request/{user_request}/response/add",
 *     "edit-form" = "/user-request/response/{user_request_response}/edit",
 *     "delete-form" = "/user-request/response/{user_request_response}/delete",
 *   }
 * )
 */
class Response extends ContentEntityBase implements ResponseInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseType() {
    return ResponseType::load($this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function respondedBy() {
    return $this->getOwner();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequest() {
    // Builds a query to get the response's request.
    $query = \Drupal::entityQuery('user_request');
    $entity_ids = $query
      ->condition('response.target_id', $this->id())
      ->execute();

    // Loads the request entity.
    if ($entity_ids) {
      $request_id = reset($entity_ids);
      $request = Request::load($request_id);
    }

    return $request ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $owner_field = $fields['owner'];
    $owner_field->setLabel('Responded by')
                ->setDisplayOptions('view', [
      'weight' => -10,
      'label' => 'inline',
    ]);

    $created_field = $fields['created'];
    $created_field->setLabel('Responded on')
                  ->setDisplayOptions('view', [
      'weight' => -5,
      'label' => 'inline',
    ]);

    return $fields;
  }

}
