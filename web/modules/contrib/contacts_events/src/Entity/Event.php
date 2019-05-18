<?php

namespace Drupal\contacts_events\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Event entity.
 *
 * @ingroup contacts_events
 *
 * @ContentEntityType(
 *   id = "contacts_event",
 *   label = @Translation("Event"),
 *   bundle_label = @Translation("Event type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\contacts_events\EventListBuilder",
 *     "views_data" = "Drupal\contacts_events\Entity\EventViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\contacts_events\Form\EventForm",
 *       "add" = "Drupal\contacts_events\Form\EventForm",
 *       "edit" = "Drupal\contacts_events\Form\EventForm",
 *       "delete" = "Drupal\contacts_events\Form\EventDeleteForm",
 *     },
 *     "access" = "Drupal\contacts_events\EventAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\contacts_events\EventHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "contacts_event",
 *   admin_permission = "administer contacts_event entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/event/{contacts_event}",
 *     "add-page" = "/event/add",
 *     "add-form" = "/event/add/{event_type}",
 *     "edit-form" = "/event/{contacts_event}/edit",
 *     "delete-form" = "/event/{contacts_event}/delete",
 *     "collection" = "/admin/content/event",
 *     "book" = "/event/{contacts_event}/book",
 *   },
 *   bundle_entity_type = "event_type",
 *   field_ui_base_route = "entity.event_type.edit_form"
 * )
 */
class Event extends ContentEntityBase implements EventInterface {

  use EntityChangedTrait;

  /**
   * Event bookings are disabled.
   */
  const STATUS_DISABLED = 'disabled';

  /**
   * Event bookings are restricted.
   */
  const STATUS_CLOSED = 'closed';

  /**
   * Event is open for bookings.
   */
  const STATUS_OPEN = 'open';

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isBookingEnabled() {
    return $this->get('booking_status')->value != self::STATUS_DISABLED;
  }

  /**
   * {@inheritdoc}
   */
  public function isBookingOpen() {
    return $this->get('booking_status')->value == self::STATUS_OPEN;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Code'))
      ->setDescription(new TranslatableMarkup('Short code for the event, also used to prefix booking numbers.'))
      ->setSettings([
        'max_length' => 31,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tagline'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Tagline'))
      ->setDescription(new TranslatableMarkup('Tagline for the event.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['date'] = BaseFieldDefinition::create('daterange')
      ->setLabel(new TranslatableMarkup('Date'))
      ->setDescription(new TranslatableMarkup('Date and time of the event.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['venue'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Venue'))
      ->setDescription(new TranslatableMarkup('Location of the event.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Starting Price'))
      ->setDescription(new TranslatableMarkup('The cheapest price for this event.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['listing_image'] = BaseFieldDefinition::create('image')
      ->setLabel(new TranslatableMarkup('Listing image'))
      ->setDescription(new TranslatableMarkup('Smaller image used in the event listings.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['banner_image'] = BaseFieldDefinition::create('image')
      ->setLabel(new TranslatableMarkup('Full Banner'))
      ->setDescription(new TranslatableMarkup('Full width banner image for the event.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Publishing status'))
      ->setDescription(new TranslatableMarkup('A boolean indicating whether the Event is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the entity was last edited.'));

    $fields['booking_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Is booking open on this event?'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('allowed_values', [
        self::STATUS_OPEN => new TranslatableMarkup('Open'),
        self::STATUS_CLOSED => new TranslatableMarkup('Closed'),
        self::STATUS_DISABLED => new TranslatableMarkup('Disabled'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'hidden',
      ])
      ->setDisplayConfigurable('view', FALSE);

    return $fields;
  }

}
