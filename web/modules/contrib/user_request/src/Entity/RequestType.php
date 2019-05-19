<?php

namespace Drupal\user_request\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Configuration entity to define request types.
 *
 * @ConfigEntityType(
 *   id = "user_request_type",
 *   label = @Translation("Request type"),
 *   label_collection = @Translation("Request types"),
 *   label_plural = @Translation("request types"),
 *   admin_permission = "administer user_request_type",
 *   bundle_of = "user_request",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "workflow",
 *     "response_type",
 *     "response_transitions",
 *     "deleted_response_transition",
 *     "messages",
 *   },
 *   handlers = {
 *     "access" = "Drupal\entity\EntityAccessControlHandler",
 *     "list_builder" = "Drupal\entity_extra\Controller\ConfigEntityListBuilder",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "local_action_provider" = {
 *       "Drupal\entity\Menu\EntityCollectionLocalActionProvider",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\user_request\Form\RequestTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/user-request/request-type/{user_request_type}",
 *     "collection" = "/admin/structure/user-request/request-type",
 *     "add-form" = "/admin/structure/user-request/request-type/add",
 *     "edit-form" = "/admin/structure/user-request/request-type/{user_request_type}/edit",
 *     "delete-form" = "/admin/structure/user-request/request-type/{user_request_type}/delete",
 *   }
 * )
 */
class RequestType extends ConfigEntityBundleBase implements RequestTypeInterface {

  /**
   * The bundle ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The workflow used for this request.
   *
   * @var string
   */
  protected $workflow;

  /**
   * The bundle of Response this request accepts.
   *
   * @var string
   */
  protected $response_type;

  /**
   * Transitions that are made upon response submission.
   *
   * @var string[]
   */
  protected $response_transitions = [];

  /**
   * The transition performed when a response is deleted.
   *
   * @var string
   */
  protected $deleted_response_transition;

  /**
   * The messages to be sent on several events.
   *
   * @var array
   */
  protected $messages;

  /**
   * {@inheritdoc}
   */
  public function setWorkflow($workflow_id) {
    $this->workflow = $workflow_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflow() {
    return $this->workflow;
  }

  /**
   * {@inheritdoc}
   */
  public function setResponseType($response_type) {
    $this->response_type = $response_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseType() {
    return $this->response_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setResponseTransitions($transitions) {
    $this->response_transitions = $transitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseTransitions() {
    return $this->response_transitions;
  }

  /**
   * {@inheritdoc}
   */
  public function isResponseTransition($transition) {
    return in_array($transition, $this->response_transitions);
  }

  /**
   * {@inheritdoc}
   */
  public function getDeletedResponseTransition() {
    return $this->deleted_response_transition;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages() {
    return $this->messages ?: [];
  }

}
