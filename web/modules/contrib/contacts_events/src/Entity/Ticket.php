<?php

namespace Drupal\contacts_events\Entity;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\contacts_events\Event\TicketContactAcquisitionEvent;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\decoupled_auth\AcquisitionService;
use Drupal\name\NameFormatParser;
use Drupal\user\UserInterface;

/**
 * Defines the Ticket entity.
 *
 * @ContentEntityType(
 *   id = "contacts_ticket",
 *   label = @Translation("Ticket"),
 *   bundle_label = @Translation("Ticket type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\contacts_events\TicketListBuilder",
 *     "views_data" = "Drupal\contacts_events\Entity\TicketViewsData",
 *     "form" = {
 *       "default" = "Drupal\contacts_events\Form\TicketForm",
 *       "add" = "Drupal\contacts_events\Form\TicketForm",
 *       "edit" = "Drupal\contacts_events\Form\TicketForm",
 *       "delete" = "Drupal\contacts_events\Form\TicketDeleteForm",
 *     },
 *     "access" = "Drupal\contacts_events\TicketAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\contacts_events\TicketHtmlRouteProvider",
 *     },
 *     "inline_form" = "Drupal\contacts_events\Form\TicketInlineForm"
 *   },
 *   base_table = "contacts_ticket",
 *   admin_permission = "administer contacts_ticket entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "creator",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/tickets/{contacts_ticket}",
 *     "add-page" = "/admin/content/tickets/add",
 *     "add-form" = "/admin/content/tickets/{contacts_ticket_type}/add",
 *     "edit-form" = "/admin/content/tickets/{contacts_ticket}/edit",
 *     "delete-form" = "/admin/content/tickets/{contacts_ticket}/delete",
 *     "collection" = "/admin/content/tickets",
 *   },
 *   bundle_entity_type = "contacts_ticket_type",
 *   field_ui_base_route = "entity.contacts_ticket_type.edit_form"
 * )
 */
class Ticket extends ContentEntityBase implements TicketInterface {

  use EntityChangedTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'creator' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $name = $this->getName();
    $event = $this->getEvent();
    $booking = $this->getBooking();
    $booking_replacement = $booking ? new FormattableMarkup(' [@booking]', ['@booking' => $this->getBooking()->label()]) : '';

    if ($name && $event) {
      return new TranslatableMarkup('Ticket for @name at @event@booking', [
        '@name' => $name,
        '@event' => $event->label(),
        '@booking' => $booking_replacement,
      ]);
    }
    elseif ($event || $name) {
      return new TranslatableMarkup('Ticket for @descriptor@booking', [
        '@descriptor' => $event ? $event->label() : $name,
        '@booking' => $booking_replacement,
      ]);
    }
    else {
      return $this->t('Unknown ticket@booking', ['@booking' => $booking_replacement]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    $name = $this->get('name');
    if (!$name->isEmpty()) {
      // @todo: Got to be a nicer way to do this... NameItem::getString?
      $format = name_get_format_by_machine_name('default');
      $name = NameFormatParser::parse($this->get('name')->get(0)->getValue(), $format, [
        'object' => $this,
        'type' => $this->getEntityTypeId(),
        'markup' => FALSE,
      ]);
      return _name_value_sanitize($name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItem() {
    return $this->get('order_item')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderItem(OrderItemInterface $order_item) {
    return $this->set('order_item', $order_item);
  }

  /**
   * {@inheritdoc}
   */
  public function getBooking() {
    $order_item = $this->getOrderItem();
    return $order_item ? $order_item->getOrder() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEvent() {
    return $this->get('event')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($name) {
    parent::onChange($name);

    // Update the event if the booking changes.
    if ($name == 'order_item') {
      $event = $this->get('order_item')->entity
        ->getOrder()
        ->get('event')->target_id;
      $this->set('event', $event);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // Run acquisitions prior to the ticket being saved.
    // Only perform acquisition if an email is specified.
    if (!empty($this->get('email')->value)) {

      // If the email has changed, we need to re-acquire.
      /* @var \Drupal\contacts_events\Entity\Ticket $original */
      $original = !empty($this->id()) ? $storage->loadUnchanged($this->id()) : NULL;

      if (!empty($this->get('contact')->target_id) && isset($original)) {
        $new_email = $this->get('email')->value;
        $original_email = $original->get('email')->value;

        if (($new_email xor $original_email) || $new_email != $original_email) {
          $this->set('contact', NULL);
        }
      }

      $this->acquire();
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function acquire($early = FALSE) {
    /* @var \Drupal\Core\Entity\EntityInterface $contact */
    // Ensure any linked contact exists.
    if (!empty($this->get('contact')->target_id)) {
      $acquisition_method = 'update';
      $contact = $this->get('contact')->entity;
      if (!$contact) {
        $this->set('contact', NULL);
      }
    }

    // If we don't have a contact, look for one.
    if (empty($this->get('contact')->target_id)) {
      $values = [];

      if (!empty($this->get('email')->value)) {
        $values['mail'] = $this->get('email')->value;
      }

      $context = [
        'name' => 'contacts_events_ticket',
        'ticket' => $this,
        // Always prefer coupled and allow acquiring protected roles.
        'behavior' => AcquisitionService::BEHAVIOR_PREFER_COUPLED | AcquisitionService::BEHAVIOR_INCLUDE_PROTECTED_ROLES,
      ];

      // In early acquisitions, we don't want to create a contact.
      if (!$early) {
        $context['behavior'] = $context['behavior'] | AcquisitionService::BEHAVIOR_CREATE;
      }

      // Use the global config for first behavior.
      if (\Drupal::config('decoupled_auth.settings')->get('acquisitions.behavior_first')) {
        $context['behavior'] = $context['behavior'] | AcquisitionService::BEHAVIOR_FIRST;
      }

      /* @var \Drupal\decoupled_auth\AcquisitionService $service */
      $service = \Drupal::service('decoupled_auth.acquisition');
      $contact = $service->acquire($values, $context, $acquisition_method);
    }

    // If we have no contact, there is nothing further to do.
    if (empty($contact)) {
      return;
    }

    // In early acquitions, we don't want to update details.
    if ($early) {
      $this->set('contact', $contact);
      return;
    }

    // If creating, we need to save before updating details.
    if ($acquisition_method == 'create') {
      // Save the new contact so we have an ID available to pass to the event.
      $contact->save();
    }

    // Raise an event to allow subscribers to attach profiles to the new entity.
    // By default we have a single subscriber that creates the Individual
    // profile.
    /* @see \Drupal\contacts_events\EventSubscriber\CreateIndividualProfileOnTicketAcquisition */
    /* @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher*/
    $dispatcher = \Drupal::service('event_dispatcher');
    $event = new TicketContactAcquisitionEvent($this, $contact, $acquisition_method);
    $dispatcher->dispatch(TicketContactAcquisitionEvent::NAME,
      $event);

    // Save any profiles that were attached to the user.
    foreach ($event->entitiesToSave as $entity) {
      $entity->save();
    }

    $this->set('contact', $contact);
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
  public function getOwner() {
    return $this->get('creator')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('creator')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('creator', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('creator', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStores() {
    $storage = \Drupal::entityTypeManager()->getStorage('commerce_store');
    $id = \Drupal::config('contacts_events.booking_settings')->get('store_id');
    return $storage->loadMultiple([$id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTypeId() {
    return 'contacts_ticket';
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTitle() {
    // In the context of a booking, we only want the name.
    if ($name = $this->getName()) {
      return new TranslatableMarkup('Ticket for @name', [
        '@name' => $name,
      ]);
    }
    else {
      return new TranslatableMarkup('Ticket');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice() {
    return $this->getPriceOverride() ?? $this->getCalculatedPrice();
  }

  /**
   * {@inheritdoc}
   */
  public function getMappedPrice() {
    $items = $this->get('mapped_price');
    return $items->count() ? $items->first()->getValue() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setMappedPrice(array $mapped_price) {
    $this->set('mapped_price', $mapped_price);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriceOverride() {
    if (!$this->get('price_override')->isEmpty()) {
      return $this->get('price_override')->first()->toPrice();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCalculatedPrice() {
    if (!$this->get('price')->isEmpty()) {
      return $this->get('price')->first()->toPrice();
    }

    return new Price(0, $this->getDefaultCurrencyCode());
  }

  /**
   * {@inheritdoc}
   */
  public function setCalculatedPrice(Price $price = NULL) {
    $this->set('price', $price->toArray());
    return $this;
  }

  /**
   * Get the default currency code.
   *
   * @return string
   *   The default currency code.
   */
  protected function getDefaultCurrencyCode() {
    $stores = $this->getStores();
    $store = reset($stores);
    return $store->getDefaultCurrencyCode();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setDefaultValueCallback(static::class . '::getDefaultStatus')
      ->setSetting('allowed_values_function', [static::class, 'getTicketStatuses'])
      ->setDisplayOptions('form', ['region' => 'hidden'])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['contact'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Contact'))
      ->setDescription(t('The contact this ticket is for.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['order_item'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order item'))
      ->setDescription(t('The order item this ticket is on.'))
      ->setSetting('target_type', 'commerce_order_item')
      ->setSetting('handler_settings', ['target_bundles' => ['contacts_ticket']])
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', ['region' => 'hidden'])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['event'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Event'))
      ->setDescription(t('The event this ticket is for.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'contacts_event')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', ['region' => 'hidden'])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price_override'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Price override'))
      ->setDescription(t('The manual price override for this ticket.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_price_default',
      ])
      ->setDisplayOptions('form', [
        'type' => 'commerce_price_default',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Price'))
      ->setDescription(t('The calculated price for this ticket.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_price_default',
      ])
      ->setDisplayOptions('form', ['region' => 'hidden'])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mapped_price'] = BaseFieldDefinition::create('mapped_price_data')
      ->setLabel(new TranslatableMarkup('Mapped price'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['creator'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Added by'))
      ->setDescription(t('The user who added this ticket.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', ['region' => 'hidden'])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setDisplayOptions('form', ['region' => 'hidden'])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);;

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setDisplayOptions('form', ['region' => 'hidden'])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('name')
      ->setLabel(t('Name'))
      ->setDescription(t("Ticket holder's name."))
      ->setRequired(TRUE)
      ->setSetting('components', [
        'title' => TRUE,
        'given' => TRUE,
        'middle' => FALSE,
        'family' => TRUE,
        'generational' => FALSE,
        'credentials' => FALSE,
      ])
      ->setSetting('labels', [
        'title' => new TranslatableMarkup('Title'),
        'given' => new TranslatableMarkup('First name'),
        'family' => new TranslatableMarkup('Surname'),
      ])
      ->setSetting('show_component_required_marker', TRUE)
      ->setSetting('title_display', [
        'title' => 'title',
        'given' => 'title',
        'family' => 'title',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t("Ticket holder's email address."))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['date_of_birth'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date of birth'))
      ->setDescription(t("Ticket holder's date of birth."))
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Implements callback_allowed_values_function().
   */
  public static function getTicketStatuses(FieldStorageDefinitionInterface $definition, FieldableEntityInterface $entity = NULL, &$cacheable = TRUE) {
    /* @var \Drupal\state_machine\Plugin\Workflow\Workflow $workflow */
    $workflow = \Drupal::service('plugin.manager.workflow')
      ->createInstance('contacts_events_order_item_process');
    $options = [];
    foreach ($workflow->getStates() as $state) {
      $options[$state->getId()] = $state->getLabel();
    }
    return $options;
  }

  /**
   * Get the default ticket status.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
   *   The field definition.
   *
   * @return string
   *   The default status.
   */
  public static function getDefaultStatus(FieldableEntityInterface $entity, FieldStorageDefinitionInterface $definition) {
    $ids = array_keys(options_allowed_values($definition, $entity));
    return reset($ids);
  }

}
