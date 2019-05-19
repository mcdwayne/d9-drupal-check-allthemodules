<?php

namespace Drupal\strava_activities\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityPublishedTrait;

/**
 * Defines the Activity entity.
 *
 * @ingroup strava
 *
 * @ContentEntityType(
 *   id = "activity",
 *   label = @Translation("Activity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\strava_activities\ActivityListBuilder",
 *     "views_data" = "Drupal\strava_activities\Entity\ActivityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\strava_activities\Form\ActivityForm",
 *       "add" = "Drupal\strava_activities\Form\ActivityForm",
 *       "edit" = "Drupal\strava_activities\Form\ActivityForm",
 *       "delete" = "Drupal\strava_activities\Form\ActivityDeleteForm",
 *       "refresh" = "Drupal\strava_activities\Form\ActivityRefreshForm",
 *     },
 *     "access" = "Drupal\strava_activities\ActivityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\strava_activities\ActivityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "activity",
 *   admin_permission = "administer activity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "published" = "status",
 *     "status" = "status",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/strava/activity/{activity}",
 *     "add-form" = "/strava/activity/add",
 *     "edit-form" = "/strava/activity/{activity}/edit",
 *     "delete-form" = "/strava/activity/{activity}/delete",
 *     "collection" = "/strava/activity",
 *   },
 *   field_ui_base_route = "activity.settings"
 * )
 */
class Activity extends ContentEntityBase implements ActivityInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Activity id'))
      ->setDescription('The unique identifier of the activity')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['external_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('External id'))
      ->setDescription('The identifier provided at upload time')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The entity language code.'));

    $fields['gear_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Gear id'))
      ->setDescription('The gear\'s unique identifier.')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['gear_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Gear name'))
      ->setDescription('The gear\'s name.')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['map_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Map id'))
      ->setDescription('The unique identifier of the map')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['map_summary_polyline'] = BaseFieldDefinition::create('strava_map_polyline')
      ->setLabel(t('Map summary polyline'))
      ->setDescription('The summary polyline of the map')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayOptions('view', [
        'type' => 'strava_map_polyline',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['map_polyline'] = BaseFieldDefinition::create('strava_map_polyline')
      ->setLabel(t('Map polyline'))
      ->setDescription('The polyline of the map')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayOptions('view', [
        'type' => 'strava_map_polyline',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['upload_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Upload id'))
      ->setDescription('The unique identifier of the upload')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['athlete'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Athlete'))
      ->setDescription('The unique identifier of the athlete')
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'athlete')
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'athlete',
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Activity.'))
      ->setSettings([
        'max_length' => 256,
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

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription('The description of the activity')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['distance'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Distance'))
      ->setDescription('The activity\'s distance, in meters')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayOptions('view', [
        'type' => 'number_distance',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['moving_time'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Moving time'))
      ->setDescription('The activity\'s moving time, in seconds')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['elapsed_time'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Elapsed time'))
      ->setDescription('The activity\'s elapsed time, in seconds')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_elevation_gain'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Total elevation gain'))
      ->setDescription('The activity\'s total elevation gain.')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayOptions('view', [
        'type' => 'number_elevation',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['elev_high'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Elevation high'))
      ->setDescription('The activity\'s highest elevation, in meters')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayOptions('view', [
        'type' => 'number_elevation',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['elev_low'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Elevation low'))
      ->setDescription('The activity\'s lowest elevation, in meters')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayOptions('view', [
        'type' => 'number_elevation',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['average_speed'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Average speed'))
      ->setDescription('The activity\'s average speed, in meters per second')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayOptions('view', [
        'type' => 'number_speed',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['max_speed'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Maximum speed'))
      ->setDescription('The activity\'s max speed, in meters per second')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayOptions('view', [
        'type' => 'number_speed',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['start_lat'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Start Latitude'))
      ->setDescription('The activity\'s start latitude')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['start_long'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Start Longitude'))
      ->setDescription('The activity\'s start longitude')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end_lat'] = BaseFieldDefinition::create('float')
      ->setLabel(t('End Latitude'))
      ->setDescription('The activity\'s end latitude')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end_long'] = BaseFieldDefinition::create('float')
      ->setLabel(t('End Longitude'))
      ->setDescription('The activity\'s end longitude')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['kilojoules'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Kilojoules'))
      ->setDescription('The total work done in kilojoules during this activity. Rides only')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['calories'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Calories'))
      ->setDescription('The number of kilocalories consumed during this activity')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['average_watts'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Average Watts'))
      ->setDescription('Average power output in watts during this activity. Rides only')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['device_watts'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Device Watts'))
      ->setDescription('Whether the watts are from a power meter, false if estimated')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['max_watts'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Maximum Watts'))
      ->setDescription('Rides with power meter data only')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weighted_average_watts'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weighted average Watts'))
      ->setDescription('Similar to Normalized Power. Rides with power meter data only')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription('The type of activity: AlpineSki, BackcountrySki, Canoeing, Crossfit, EBikeRide, Elliptical, Hike, IceSkate, InlineSkate, Kayaking, Kitesurf, NordicSki, Ride, RockClimbing, RollerSki, Rowing, Run, Snowboard, Snowshoe, StairStepper, StandUpPaddling, Surfing, Swim, VirtualRide, VirtualRun, Walk, WeightTraining, Windsurf, Workout, Yoga')
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

    $fields['achievement_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Achievement count'))
      ->setDescription('The number of achievements gained during this activity')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['kudos_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Kudos count'))
      ->setDescription('The number of kudos given for this activityThe number of kudos given for this activity')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['comment_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Comment count'))
      ->setDescription('The number of comments for this activity')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['athlete_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Athlete count'))
      ->setDescription('The number of athletes for taking part in a group activity')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['photo_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Photo count'))
      ->setDescription('The number of Instagram photos for this activity')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_photo_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total photo count'))
      ->setDescription('The number of Instagram and Strava photos for this activity')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['photo'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Photo url'))
      ->setSettings([
        'max_length' => 512,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'uri_image',
      ])
      ->setDisplayOptions('form', [
        'type' => 'uri',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['small_photo'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Small photo url'))
      ->setSettings([
        'max_length' => 512,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'uri_image',
      ])
      ->setDisplayOptions('form', [
        'type' => 'uri',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['trainer'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Trainer'))
      ->setDescription('Whether this activity was recorded on a training machine')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['commute'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Commute'))
      ->setDescription('Whether this activity is a commute')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['manual'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Manual'))
      ->setDescription('Whether this activity was created manually')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['flagged'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Flagged'))
      ->setDescription('Whether this activity is flagged')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['private'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Private'))
      ->setDescription('Whether this activity is private')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Activity is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

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
  public function getAthlete() {
    return $this->get('athlete')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAthlete($athlete_id) {
    $this->set('athlete', $athlete_id);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * @inheritDoc
   */
  public function setDescription($description) {
    $this->set('description', $description);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getDistance() {
    return $this->get('distance')->value;
  }

  /**
   * @inheritDoc
   */
  public function setDistance($distance) {
    $this->set('distance', $distance);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getMovingTime() {
    return $this->get('moving_time')->value;
  }

  /**
   * @inheritDoc
   */
  public function setMovingTime($time) {
    $this->set('moving_time', $time);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getElapsedTime() {
    return $this->get('elapsed_time')->value;
  }

  /**
   * @inheritDoc
   */
  public function setElapsedTime($time) {
    $this->set('elapsed_time', $time);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getElevationGain() {
    return $this->get('total_elevation_gain')->value;
  }

  /**
   * @inheritDoc
   */
  public function setElevationGain($elevation) {
    $this->set('total_elevation_gain', $elevation);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getElevationHigh() {
    return $this->get('elev_high')->value;
  }

  /**
   * @inheritDoc
   */
  public function setElevationHigh($elevation) {
    $this->set('elev_high', $elevation);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getElevationLow() {
    return $this->get('elev_low')->value;
  }

  /**
   * @inheritDoc
   */
  public function setElevationLow($elevation) {
    $this->set('elev_low', $elevation);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getAverageSpeed() {
    return $this->get('average_speed')->value;
  }

  /**
   * @inheritDoc
   */
  public function setAverageSpeed($speed) {
    $this->set('average_speed', $speed);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getMaxSpeed() {
    return $this->get('max_speed')->value;
  }

  /**
   * @inheritDoc
   */
  public function setMaxSpeed($speed) {
    $this->set('max_speed', $speed);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getKiloJoules() {
    return $this->get('kilojoules')->value;
  }

  /**
   * @inheritDoc
   */
  public function setKiloJoules($kilojoules) {
    $this->set('kilojoules', $kilojoules);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getCalories() {
    return $this->get('calories')->value;
  }

  /**
   * @inheritDoc
   */
  public function setCalories($calories) {
    $this->set('calories', $calories);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getAverageWatts() {
    return $this->get('average_watts')->value;
  }

  /**
   * @inheritDoc
   */
  public function setAverageWatts($watts) {
    $this->set('average_watts', $watts);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getDeviceWatts() {
    return $this->get('device_watts')->value;
  }

  /**
   * @inheritDoc
   */
  public function setDeviceWatts($device) {
    $this->set('device_watts', $device);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getMaxWatts() {
    return $this->get('max_watts')->value;
  }

  /**
   * @inheritDoc
   */
  public function setMaxWatts($watts) {
    $this->set('max_watts', $watts);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getWeightedAverageWatts() {
    return $this->get('weighted_average_watts')->value;
  }

  /**
   * @inheritDoc
   */
  public function setWeightedAverageWatts($watts) {
    $this->set('weighted_average_watts', $watts);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * @inheritDoc
   */
  public function setType($type) {
    $this->set('type', $type);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getAchievementCount() {
    return $this->get('achievement_count')->value;
  }

  /**
   * @inheritDoc
   */
  public function setAchievementCount($count) {
    $this->set('achievement_count', $count);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getKudosCount() {
    return $this->get('kudos_count')->value;
  }

  /**
   * @inheritDoc
   */
  public function setKudosCount($count) {
    $this->set('kudos_count', $count);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getCommentCount() {
    return $this->get('comment_count')->value;
  }

  /**
   * @inheritDoc
   */
  public function setCommentCount($count) {
    $this->set('comment_count', $count);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getPhotoCount() {
    return $this->get('photo_count')->value;
  }

  /**
   * @inheritDoc
   */
  public function setPhotoCount($count) {
    $this->set('photo_count', $count);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getTotalPhotoCount() {
    return $this->get('total_photo_count')->value;
  }

  /**
   * @inheritDoc
   */
  public function setTotalPhotoCount($count) {
    $this->set('total_photo_count', $count);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getPhoto() {
    return $this->get('photo')->value;
  }

  /**
   * @inheritDoc
   */
  public function setPhoto($photo) {
    $this->set('photo', $photo);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getSmallPhoto() {
    return $this->get('small_photo')->value;
  }

  /**
   * @inheritDoc
   */
  public function setSmallPhoto($photo) {
    $this->set('small_photo', $photo);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getMapId() {
    return $this->get('map_id')->value;
  }

  /**
   * @inheritDoc
   */
  public function setMapId($id) {
    $this->set('map_id', $id);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getMapSummaryPolyline() {
    return $this->get('map_summary_polyline')->value;
  }

  /**
   * @inheritDoc
   */
  public function setMapSummaryPolyline($polyline) {
    $this->set('map_summary_polyline', $polyline);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getGearId() {
    return $this->get('gear_id')->value;
  }

  /**
   * @inheritDoc
   */
  public function setGearId($id) {
    $this->set('gear_id', $id);
  }

  /**
   * @inheritDoc
   */
  public function getGearName() {
    return $this->get('gear_name')->value;
  }

  /**
   * @inheritDoc
   */
  public function setGearName($name) {
    $this->set('gear_name', $name);
  }

  /**
   * @inheritDoc
   */
  public function getMapPolyline() {
    return $this->get('map_polyline')->value;
  }

  /**
   * @inheritDoc
   */
  public function setMapPolyline($polyline) {
    $this->set('map_polyline', $polyline);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getAthleteCount() {
    return $this->get('athlete_count')->value;
  }

  /**
   * @inheritDoc
   */
  public function setAthleteCount($count) {
    $this->set('athlete_count', $count);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getTrainer() {
    return $this->get('trainer')->value;
  }

  /**
   * @inheritDoc
   */
  public function setTrainer($trainer) {
    $this->set('trainer', $trainer);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getCommute() {
    return $this->get('commute')->value;
  }

  /**
   * @inheritDoc
   */
  public function setCommute($commute) {
    $this->set('commute', $commute);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getManual() {
    return $this->get('manual')->value;
  }

  /**
   * @inheritDoc
   */
  public function setManual($manual) {
    $this->set('manual', $manual);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getFlagged() {
    return $this->get('flagged')->value;
  }

  /**
   * @inheritDoc
   */
  public function setFlagged($flagged) {
    $this->set('flagged', $flagged);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getPrivate() {
    return $this->get('private')->value;
  }

  /**
   * @inheritDoc
   */
  public function setPrivate($private) {
    $this->set('private', $private);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getStartLatLong() {
    return [
      'lat' => $this->get('start_lat')->value,
      'long' => $this->get('start_long')->value,
    ];
  }

  /**
   * @inheritDoc
   */
  public function setStartLatLong($lat, $long) {
    $this->set('start_lat', $lat);
    $this->set('start_long', $long);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getEndLatLong() {
    return [
      'lat' => $this->get('end_lat')->value,
      'long' => $this->get('end_long')->value,
    ];
  }

  /**
   * @inheritDoc
   */
  public function setEndLatLong($lat, $long) {
    $this->set('end_lat', $lat);
    $this->set('end_long', $long);
    return $this;
  }
}
