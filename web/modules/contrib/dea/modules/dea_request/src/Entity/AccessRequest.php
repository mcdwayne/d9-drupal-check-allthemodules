<?php

namespace Drupal\dea_request\Entity;

use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountProxy;
use Drupal\user\Entity\User;

/**
 * @ContentEntityType(
 *   id = "dea_request",
 *   label = @Translation("Access Request"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\dea_request\Entity\AccessRequestListBuilder",
 *     "form" = {
 *       "request" = "Drupal\dea_request\Form\RequestForm",
 *       "accept" = "Drupal\dea_request\Form\AcceptForm",
 *       "deny" = "Drupal\dea_request\Form\DenyForm",
 *       "delete" = "Drupal\dea_request\Form\DeleteForm",
 *     },
 *     "access" = "Drupal\dea_request\Entity\AccessRequestAccessControlHandler",
 *   },
 *   base_table = "dea_request",
 *   admin_permission = "administer users",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/content/dea_requests/request/{dea_request}",
 *     "accept" = "/admin/content/dea_requests/{dea_request}/accept",
 *     "deny" = "/admin/content/dea_requests/{dea_request}/deny",
 *     "delete" = "/admin/content/dea_requests/{dea_request}/delete",
 *     "list" = "/admin/content/dea_requests",
 *   },
 *   field_ui_base_route = "dea_request.settings",
 * )
 */
class AccessRequest extends ContentEntityBase {

  const OPEN = 0;

  const ACCEPTED = 1;

  const DENIED = 2;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return '#' . $this->id();
  }


  public function setOwner(AccountProxy $user) {
    $this->values['uid'] = $user->id();
  }

  public function getOwner() {
    return User::load($this->get('uid')->first()->getValue()['target_id']);
  }
  
  public function getOperation() {
    $this->get('operation')->first()->getValue()['value'];
  }

  public function setTarget(EntityInterface $entity) {
    $this->values['entity_type'] = $entity->getEntityTypeId();
    $this->values['entity_id'] = $entity->id();
  }

  public function getTarget() {
    $entity_type = $this->get('entity_type')->first()->getValue()['value'];
    $entity_id = $this->get('entity_id')->first()->getValue()['value'];
    if (!$entity_type || !$entity_id) {
      return NULL;
    }
    $entity_manager = \Drupal::entityManager();
    return $entity_manager->getStorage($entity_type)->load($entity_id);
  }

  public function getReadableStatus() {
    $opts = ['context' => 'access-request-status'];
    return [
      AccessRequest::OPEN => t('Open', $opts),
      AccessRequest::ACCEPTED => t('Accepted', $opts),
      AccessRequest::DENIED => t('Denied', $opts),
    ][$this->status->value];
  }

  public function getStatus() {
    return $this->status->value;
  }


  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the request entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the request entity.'))
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Name'))
      ->setDescription(t('The name of the requesting user.'))
      ->setSetting('target_type', 'user');

    $fields['request_path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Request'))
      ->setDescription(t('The destination request path the user tried to access.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The type of the requested entity.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The id of the requested entity.'));

    $fields['operation'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Operation'))
      ->setDescription(t('The operation the user requests.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setDefaultValue(AccessRequest::OPEN)
      ->setDescription(t('The request status.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
