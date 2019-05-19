<?php

namespace Drupal\user_request\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Request entity.
 *
 * @ContentEntityType(
 *   id = "user_request",
 *   label = @Translation("Request"),
 *   label_plural = @Translation("requests"),
 *   label_collection = @Translation("Requests"),
 *   base_table = "user_request",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.user_request_type.edit_form",
 *   bundle_entity_type = "user_request_type",
 *   bundle_label = @Translation("Request type"),
 *   admin_permission = "administer user_request",
 *   permission_granularity = "bundle",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "subject",
 *     "bundle" = "type",
 *     "created" = "created",
 *     "changed" = "changed",
 *     "owner" = "owner",
 *     "uuid" = "uuid"
 *   },
 *   handlers = {
 *     "access" = "Drupal\user_request\Access\RequestAccessControlHandler",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\entity_extra\Controller\ViewsEntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "permission_provider" = "Drupal\user_request\Access\RequestPermissionProvider",
 *     "local_action_provider" = {
 *       "Drupal\entity\Menu\EntityCollectionLocalActionProvider",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\user_request\Routing\RequestHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\entity_extra\Form\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *   },
 *   links = {
 *     "canonical" = "/user-request/{user_request}",
 *     "add-page" = "/user-request/add",
 *     "add-form" = "/user-request/add/{user_request_type}",
 *     "edit-form" = "/user-request/{user_request}/edit",
 *     "delete-form" = "/user-request/{user_request}/delete",
 *   }
 * )
 */
class Request extends ContentEntityBase implements RequestInterface {

  /**
   * The response that was removed.
   *
   * @var \Drupal\user_request\Entity\ResponseInterface;
   */
  protected $removedResponse;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $owner_field = $fields['owner'];
    $owner_field->setLabel('Sent by')
                ->setDisplayOptions('view', [
      'weight' => -10,
      'label' => 'inline',
    ]);

    $created_field = $fields['created'];
    $created_field->setLabel('Sent on')
                  ->setDisplayOptions('view', [
      'weight' => -5,
      'label' => 'inline',
    ]);

    $subject_field = $fields['subject'];
    $subject_field->setLabel(t('Subject'))
                  ->setDescription('');

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('State'))
      ->setDisplayOptions('view', [
        'weight' => 10,
        'label' => 'inline',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setRevisionable(TRUE);

    $fields['recipients'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Recipients'))
      ->setDescription(t('The users that can respond to this request.'))
      ->setDisplayOptions('view', [
        'weight' => 20,      
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'user')
      ->setSetting('handler_settings', [
        'include_anonymous' => FALSE,
      ])
      ->setRequired(TRUE);

    $fields['response'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Response'))
      ->setDisplayOptions('view', [
        'weight' => 30,
        'type' => 'entity_reference_entity_view',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('target_type', 'user_request_response');

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    // Gets the bundle configuration.
    $bundle_config = RequestType::load($bundle);

    // Overrides the state fiel to set the workflow.
    $state_field = $base_field_definitions['state'];
    $state_field->setSetting('workflow', $bundle_config->getWorkflow());
    $fields['state'] = $state_field;

    // Overrides the response field to set target response type.
    $response_field = $base_field_definitions['response'];
    $response_field->setSetting('handler_settings', [
      'target_bundles' => [
        'user_request_response' => $bundle_config->getResponseType(),
      ],
    ]);
    $fields['response'] = $response_field;

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestType() {
    return RequestType::load($this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function requestedBy() {
    return $this->getOwner();
  }


  /**
   * {@inheritdoc}
   */
  public function setRecipients(array $recipients) {
    $this->recipients = $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients() {
    return $this->recipients->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    return $this->response->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function applyTransition($transition) {
    $this->getState()->applyTransitionById($transition);
  }

  /**
   * {@inheritdoc}
   */
  public function respond($transition, ResponseInterface $response) {
    $request_type = $this->getRequestType();

    // Checks if transition is allowed for response.
    if (!in_array($transition, $request_type->getResponseTransitions())) {
      throw new \InvalidArgumentException('Transition is not allowed when responding.');
    }

    // Checks the response type.
    if ($request_type->getResponseType() != $response->bundle()) {
      throw new \InvalidArgumentException('Invalid response type.');
    }

    // Checks if already has a response.
    if ($this->getResponse()) {
      throw new \LogicException('Cannot respond previously responded request.');
    }

    // Applies the transition.
    $this->applyTransition($transition);

    // Sets the response.
    $this->response->entity = $response;
  }

  /**
   * {@inheritdoc}
   */
  public function removeResponse() {
    $this->removedResponse = $this->getResponse();
    $this->response->target_id = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemovedResponse() {
    return $this->removedResponse;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->state->first();
  }

  /**
   * {@inheritdoc}
   */
  public function getStateString() {
    $state = $this->getState();
    return $state->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function inState($state) {
    return $this->getStateString() == $state;
  }

  /**
   * {@inheritdoc}
   */
  public function hasResponse() {
    return !$this->response->isEmpty();
  }

}
