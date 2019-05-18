<?php

namespace Drupal\entity_pilot\Entity;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_pilot\ArrivalInterface;
use Drupal\entity_pilot\FlightBase;
use Drupal\entity_pilot\FlightInterface;
use Drupal\rest\LinkManager\TypeLinkManagerInterface;

/**
 * Defines the flight entity class.
 *
 * @ContentEntityType(
 *   id = "ep_arrival",
 *   label = @Translation("Entity Pilot Arrival"),
 *   bundle_label = @Translation("Entity Pilot Account"),
 *   handlers = {
 *     "storage" = "Drupal\entity_pilot\Storage\ArrivalStorage",
 *     "access" = "Drupal\entity_pilot\Access\ArrivalAccessControlHandler",
 *     "list_builder" = "Drupal\entity_pilot\ListBuilders\ArrivalListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_pilot\Form\ArrivalAddForm",
 *       "edit" = "Drupal\entity_pilot\Form\ArrivalForm",
 *       "delete" = "Drupal\entity_pilot\Form\ArrivalDeleteForm",
 *       "default" = "Drupal\entity_pilot\Form\ArrivalForm",
 *       "approve" = "Drupal\entity_pilot\Form\ArrivalApproveForm",
 *       "queue" = "Drupal\entity_pilot\Form\ArrivalQueueForm",
 *     },
 *   },
 *   admin_permission = "administer entity_pilot arrivals",
 *   base_table = "entity_pilot_arrival",
 *   revision_table = "entity_pilot_arrival_revision",
 *   field_ui_base_route = "entity_pilot.account_edit_arrivals",
 *   links = {
 *     "canonical" = "/admin/structure/entity-pilot/arrivals/{ep_arrival}",
 *     "delete-form" = "/admin/structure/entity-pilot/arrivals/{ep_arrival}/delete",
 *     "edit-form" = "/admin/structure/entity-pilot/arrivals/{ep_arrival}/edit",
 *     "approve-form" = "/admin/structure/entity-pilot/arrivals/{ep_arrival}/approve",
 *     "queue-form" = "/admin/structure/entity-pilot/arrivals/{ep_arrival}/queue"
 *   },
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "bundle" = "account",
 *     "label" = "info",
 *     "uuid" = "uuid"
 *   },
 *   bundle_entity_type = "ep_account"
 * )
 */
class Arrival extends FlightBase implements ArrivalInterface {

  /**
   * Static cache of decoded entities.
   *
   * @var array
   */
  protected $decoded = [];

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Arrival ID'))
      ->setDescription(t('The flight ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The Arrival UUID.'))
      ->setReadOnly(TRUE);

    $fields['revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The flight language code.'));

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setDescription(t('The status ID.'))
      ->setSetting('unsigned', TRUE);

    $fields['remote_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Remote ID'))
      ->setDescription(t('The remote ID.'))
      ->setSetting('unsigned', TRUE);

    $fields['info'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Arrival description'))
      ->setDescription(t('A brief description of your flight.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['log'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Revision log message'))
      ->setDescription(t('The revision log message.'))
      ->setRevisionable(TRUE);

    $fields['account'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Account'))
      ->setDescription(t('The flight account.'))
      ->setSetting('target_type', 'ep_account')
      ->setSetting('max_length', EntityTypeInterface::BUNDLE_MAX_LENGTH);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the flight was last edited.'))
      ->setRevisionable(TRUE);

    $fields['contents'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Flight contents'))
      ->setDescription(t('JSON encoded contents of the flight.'))
      ->setSetting('text_processing', 0)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['approved_passengers'] = BaseFieldDefinition::create('ep_approved_passengers')
      ->setLabel((string) new TranslatableMarkup('Approved passengers'))
      ->setDescription((string) new TranslatableMarkup('Select content from this flight you wish to import.'))
      ->setRequired(FALSE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'ep_approved_passengers',
        'weight' => 20,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'ep_approved_passengers',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['linked_departure'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Linked departure'))
      ->setDescription(t('The linked departure.'))
      ->setSetting('target_type', 'ep_departure');

    $fields['field_map'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Flight field map'))
      ->setDescription(t('JSON encoded field map of the flight.'))
      ->setSetting('text_processing', 0)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getContents() {
    return $this->get('contents')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);
    // Default status to pending.
    if (!$this->getStatus()) {
      $this->setStatus(FlightInterface::STATUS_PENDING);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setContents($contents) {
    $this->set('contents', $contents);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPassengers($uuid = NULL) {
    if (empty($this->decoded)) {
      $contents = $this->get('contents')->value;
      $this->decoded = json_decode($contents, TRUE) ?: [];
    }
    if (!$uuid) {
      return $this->decoded;
    }
    if (isset($this->decoded[$uuid])) {
      return $this->decoded[$uuid];
    }
    throw new \InvalidArgumentException(sprintf('No such id %s exists', $uuid));
  }

  /**
   * {@inheritdoc}
   */
  public function getApproved() {
    $approved = [];
    foreach ($this->get('approved_passengers') as $item) {
      $approved[] = $item->value;
    }
    return $approved;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    /* @var \Drupal\entity_pilot\ArrivalStorageInterface $storage */
    parent::postSave($storage, $update);
    if ($update) {
      if (!$this->original->isQueued() && $this->isQueued()) {
        // Item has been queued.
        $storage->queue($this);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createLinkedDeparture(EntityManagerInterface $entity_manager, TypeLinkManagerInterface $type_link_manager) {
    /* @var \Drupal\entity_pilot\DepartureInterface $departure */
    $departure = Departure::create([
      'account' => $this->getAccount()->id(),
    ]);
    $departure
      ->setRemoteId($this->getRemoteId())
      ->setInfo($this->getInfo())
      ->setRevisionLog(sprintf('Cloned from Arrival %s', $this->id()))
      ->setStatus(FlightInterface::STATUS_PENDING);
    $passengers = [];
    foreach ($this->getApproved() as $uuid) {
      $passenger = $this->getPassengers($uuid);
      if ($type = $type_link_manager->getTypeInternalIds($passenger['_links']['type']['href'])) {
        if ($entity = $entity_manager->loadEntityByUuid($type['entity_type'], $uuid)) {
          $passengers[] = [
            'target_type' => $type['entity_type'],
            'target_id' => $entity->id(),
          ];
        }
      }
    }
    $departure->setPassengers($passengers)
      ->save();
    $this->set('linked_departure', $departure->id());
    $this->save();
    return $departure;
  }

  /**
   * {@inheritdoc}
   */
  public function hasLinkedDeparture() {
    return (bool) $this->get('linked_departure')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkedDeparture() {
    if (!$this->hasLinkedDeparture()) {
      return FALSE;
    }
    return $this->get('linked_departure')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMap() {
    return Json::decode($this->get('field_map')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldMap($field_map) {
    $this->set('field_map', $field_map);
    return $this;
  }

}
