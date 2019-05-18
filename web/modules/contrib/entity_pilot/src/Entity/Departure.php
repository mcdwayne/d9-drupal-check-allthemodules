<?php

namespace Drupal\entity_pilot\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_pilot\Data\FlightManifest;
use Drupal\entity_pilot\DepartureInterface;
use Drupal\entity_pilot\FlightInterface;
use Drupal\entity_pilot\FlightBase;

/**
 * Defines the flight entity class.
 *
 * @ContentEntityType(
 *   id = "ep_departure",
 *   label = @Translation("Entity Pilot Departure"),
 *   bundle_label = @Translation("Entity Pilot Account"),
 *   handlers = {
 *     "storage" = "Drupal\entity_pilot\Storage\DepartureStorage",
 *     "access" = "Drupal\entity_pilot\Access\DepartureAccessControlHandler",
 *     "list_builder" = "Drupal\entity_pilot\ListBuilders\DepartureListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_pilot\Form\DepartureForm",
 *       "edit" = "Drupal\entity_pilot\Form\DepartureForm",
 *       "delete" = "Drupal\entity_pilot\Form\DepartureDeleteForm",
 *       "default" = "Drupal\entity_pilot\Form\DepartureForm",
 *       "approve" = "Drupal\entity_pilot\Form\DepartureApproveForm",
 *       "queue" = "Drupal\entity_pilot\Form\DepartureQueueForm",
 *     },
 *   },
 *   admin_permission = "administer entity_pilot departures",
 *   base_table = "entity_pilot_departure",
 *   revision_table = "entity_pilot_departure_revision",
 *   field_ui_base_route = "entity.ep_account.edit_form",
 *   links = {
 *     "canonical" = "/admin/structure/entity-pilot/departures/{ep_departure}",
 *     "delete-form" = "/admin/structure/entity-pilot/departures/{ep_departure}/delete",
 *     "edit-form" = "/admin/structure/entity-pilot/departures/{ep_departure}/edit",
 *     "approve-form" = "/admin/structure/entity-pilot/departures/{ep_departure}/approve",
 *     "queue-form" = "/admin/structure/entity-pilot/departures/{ep_departure}/queue"
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
class Departure extends FlightBase implements DepartureInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Departure ID'))
      ->setDescription(t('The flight ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The Departure UUID.'))
      ->setReadOnly(TRUE);

    $fields['revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The flight language code.'));

    $fields['info'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Departure description'))
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

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setRevisionable(TRUE)
      ->setDescription(t('The status ID.'))
      ->setSetting('unsigned', TRUE);

    $fields['remote_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Remote ID'))
      ->setDescription(t('The remote ID.'))
      ->setSetting('unsigned', TRUE);

    $fields['account'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Account'))
      ->setDescription(t('The flight account.'))
      ->setSetting('target_type', 'ep_account')
      ->setSetting('max_length', EntityTypeInterface::BUNDLE_MAX_LENGTH);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the flight was last edited.'))
      ->setRevisionable(TRUE);

    $fields['passenger_list'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel((string) new TranslatableMarkup('Passengers'))
      ->setDescription((string) new TranslatableMarkup('Select the passengers for this Entity flight.'))
      ->setRequired(FALSE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'dynamic_entity_reference_default',
        'weight' => 20,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'dynamic_entity_reference_label',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSettings([
        'exclude_entity_types' => TRUE,
        'entity_type_ids' => ['ep_departure', 'ep_account'],
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    if ($this->isNewRevision()) {
      // When inserting either a new flight or a new flight revision
      // $entity->log must be set because entity_pilot_flight_revision.log
      // is a text column and therefore cannot have a default value. However,
      // it might not be set at this point (for example, if the user submitting
      // the form does not have permission to create revisions), so we ensure
      // that it is at least an empty string in that case.
      if (!isset($record->log)) {
        $record->log = '';
      }
    }
    elseif (isset($this->original) && (!isset($record->log) || $record->log === '')) {
      // If we are updating an existing flight without adding a new revision and
      // the user did not supply a log, keep the existing one.
      $record->log = $this->original->getRevisionLog();
    }
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
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    /** @var \Drupal\entity_pilot\DepartureStorageInterface $storage */
    parent::postSave($storage, $update);
    if ($update) {
      if (!$this->original->isQueued() && $this->isQueued()) {
        // Item has been queued.
        $storage->queue($this);
      }
    }
  }

  /**
   * Gets the passengers (entities) in the departure.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of entities.
   */
  public function getPassengers() {
    $return = [];
    foreach ($this->get('passenger_list') as $value) {
      if ($value->entity) {
        $return[$value->entity->uuid()] = $value->entity;
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function createManifest(array $passengers) {
    return FlightManifest::create()
      ->setCarrierId($this->getAccount()->getCarrierId())
      ->setLog($this->getRevisionLog())
      ->setChanged($this->getChangedTime())
      ->setInfo($this->getInfo())
      ->setRemoteId($this->getRemoteId())
      ->setBlackBoxKey($this->getAccount()->getBlackBoxKey())
      ->setContents($passengers);
  }

  /**
   * {@inheritdoc}
   */
  public function setPassengers(array $passengers) {
    $this->set('passenger_list', $passengers);
    return $this;
  }

}
