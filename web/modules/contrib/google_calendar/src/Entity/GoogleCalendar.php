<?php

namespace Drupal\google_calendar\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\google_calendar\GoogleCalendarImportEvents;
use Drupal\user\UserInterface;

/**
 * Defines the Google Calendar entity.
 *
 * @ingroup google_calendar
 *
 * @ContentEntityType(
 *   id = "google_calendar",
 *   label = @Translation("Google Calendar"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\google_calendar\GoogleCalendarListBuilder",
 *     "views_data" = "Drupal\google_calendar\Entity\GoogleCalendarViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\google_calendar\Form\GoogleCalendarForm",
 *       "add" = "Drupal\google_calendar\Form\GoogleCalendarForm",
 *       "edit" = "Drupal\google_calendar\Form\GoogleCalendarForm",
 *       "delete" = "Drupal\google_calendar\Form\GoogleCalendarDeleteForm",
 *     },
 *     "access" = "Drupal\google_calendar\GoogleCalendarAccessControlHandler",
 *     "route_provider" = {
 *        "html" = "Drupal\google_calendar\GoogleCalendarHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "google_calendar",
 *   data_table = "google_calendar_field_data",
 *   admin_permission = "administer google calendars",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/calendar/{google_calendar}",
 *     "add-form" = "/admin/google-calendar/calendar/add",
 *     "edit-form" = "/admin/google-calendar/calendar/{google_calendar}/edit",
 *     "delete-form" = "/admin/google-calendar/calendar/{google_calendar}/delete",
 *     "collection" = "/admin/google-calendar/calendar",
 *   },
 *   field_ui_base_route = "google_calendar.settings"
 * )
 */
class GoogleCalendar extends ContentEntityBase implements GoogleCalendarInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
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
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
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
  public function getGoogleCalendarId(){
    return $this->get('calendar_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(){
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription(string $desc){
    $this->set('description', $desc);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation(){
    return $this->get('location')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocation(string $locn){
    $this->set('location', $locn);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSyncResult(){
    return $this->get('sync_result')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSyncResult(string $result){
    $this->set('sync_result', $result);
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function getLatestEventTime() {
    return $this->get('latest_event')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLatestEventTime($timestamp) {
    $this->set('latest_event', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastSyncTime() {
    return $this->get('last_checked')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastSyncTime($timestamp) {
    $this->set('last_checked', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preImport(string $calendarId, GoogleCalendarImportEvents $calendarImportEvents) {
  }

  /**
   * {@inheritdoc}
   */
  public function postImport(string $calendarId, GoogleCalendarImportEvents $calendarImportEvents) {
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Google Calendar entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of the Google Calendar entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Location'))
      ->setDescription(t('The (default) location of the Google Calendar entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['calendar_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Google Calendar ID'))
      ->setDescription(t('The Google ID of the calendar. This can be obtained "
          ."from the "Integrate Calendar" section of your calendar\'s settings.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Google Calendar is published.'))
      ->setDefaultValue(TRUE);

    $fields['sync_result'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sync Result'))
      ->setDescription(t('Report status of the last sync with Google.'));

    $fields['last_checked'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last Checked'))
      ->setDescription(t('The most recent time that calendar events were synced with Google.'));

    $fields['latest_event'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Latest Event'))
      ->setDescription(t('The time that events were last updated in a sync.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the calendar was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the calendar was last edited.'));

    return $fields;
  }

}
