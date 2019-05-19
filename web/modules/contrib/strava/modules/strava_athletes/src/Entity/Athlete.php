<?php

namespace Drupal\strava_athletes\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Athlete entity.
 *
 * @ingroup strava
 *
 * @ContentEntityType(
 *   id = "athlete",
 *   label = @Translation("Athlete"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\strava_athletes\AthleteListBuilder",
 *     "views_data" = "Drupal\strava_athletes\Entity\AthleteViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\strava_athletes\Form\AthleteForm",
 *       "add" = "Drupal\strava_athletes\Form\AthleteForm",
 *       "edit" = "Drupal\strava_athletes\Form\AthleteForm",
 *       "delete" = "Drupal\strava_athletes\Form\AthleteDeleteForm",
 *       "refresh" = "Drupal\strava_athletes\Form\AthleteRefreshForm",
 *     },
 *     "access" = "Drupal\strava_athletes\AthleteAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\strava_athletes\AthleteHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "athlete",
 *   admin_permission = "administer athlete entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "firstname",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "changed" = "changed",
 *     "status" = "status",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/strava/athletes/athlete/{athlete}",
 *     "add-form" = "/strava/athletes/athlete/add",
 *     "edit-form" = "/strava/athletes/athlete/{athlete}/edit",
 *     "delete-form" = "/strava/athletes/athlete/{athlete}/delete",
 *     "collection" = "/strava/athletes/athlete",
 *   },
 *   field_ui_base_route = "athlete.settings"
 * )
 */
class Athlete extends ContentEntityBase implements AthleteInterface {

  use EntityChangedTrait;

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

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User id'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Athlete id'))
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode']->setLabel(t('Language code'))
      ->setDescription(t('The entity language code.'));

    $fields['firstname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Firstname'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['lastname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Lastname'))
      ->setSettings([
        'max_length' => 50,
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

    $fields['profile'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Large profile picture url'))
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

    $fields['medium_profile'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Medium profile picture url'))
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

    $fields['city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('City'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('State'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['country'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Country'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sex'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sex'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['premium'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Premium'))
      ->setDefaultValue(TRUE);

    $fields['follower_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Follower count'))
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['friend_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Friend count'))
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ftp'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('FTP'))
      ->setDescription('The athlete\'s FTP (Functional Threshold Power).')
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['measurement_preference'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Measurement preference'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Weight'))
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'type' => 'number_weight',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['clubs'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Clubs'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'club')
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE)
      ->setCardinality(-1)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'club',
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Athlete is published.'))
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
   * @inheritdoc
   */
  public function getUid() {
    return $this->get('uid')->value;
  }

  /**
   * @inheritdoc
   */
  public function setUid($uid) {
    $this->set('uid', $uid);
  }

  /**
   * @inheritdoc
   */
  public function getId() {
    return $this->get('id')->value;
  }

  /**
   * @inheritdoc
   */
  public function setId($id) {
    $this->set('id', $id);
  }

  /**
   * @inheritdoc
   */
  public function setFirstName($firstname) {
    $this->set('firstname', $firstname);
  }

  /**
   * @inheritdoc
   */
  public function setLastName($lastname) {
    $this->set('lastname', $lastname);
  }

  /**
   * @inheritdoc
   */
  public function getProfile() {
    return $this->get('profile')->value;
  }

  /**
   * @inheritdoc
   */
  public function setProfile($profile) {
    $this->set('profile', $profile);
  }

  /**
   * @inheritdoc
   */
  public function getMediumProfile() {
    return $this->get('medium_profile')->value;
  }

  /**
   * @inheritdoc
   */
  public function setMediumProfile($profile) {
    $this->set('medium_profile', $profile);
  }

  /**
   * @inheritdoc
   */
  public function getCity() {
    return $this->get('city')->value;
  }

  /**
   * @inheritdoc
   */
  public function setCity($city) {
    $this->set('city', $city);
  }

  /**
   * @inheritdoc
   */
  public function getState() {
    return $this->get('state')->value;
  }

  /**
   * @inheritdoc
   */
  public function setState($state) {
    $this->set('state', $state);
  }

  /**
   * @inheritdoc
   */
  public function getCountry() {
    return $this->get('country')->value;
  }

  /**
   * @inheritdoc
   */
  public function setCountry($country) {
    $this->set('country', $country);
  }

  /**
   * @inheritdoc
   */
  public function getSex() {
    return $this->get('sex')->value;
  }

  /**
   * @inheritdoc
   */
  public function setSex($sex) {
    $this->set('sex', $sex);
  }

  /**
   * @inheritdoc
   */
  public function getPremium() {
    return $this->get('premium')->value;
  }

  /**
   * @inheritdoc
   */
  public function setPremium($premium) {
    $this->set('premium', $premium);
  }

  /**
   * @inheritdoc
   */
  public function getFollowerCount() {
    return $this->get('follower_count')->value;
  }

  /**
   * @inheritdoc
   */
  public function setFollowerCount($count) {
    $this->set('follower_count', $count);
  }

  /**
   * @inheritdoc
   */
  public function getFriendCount() {
    return $this->get('friend_count')->value;
  }

  /**
   * @inheritdoc
   */
  public function setFriendCount($count) {
    $this->set('friend_count', $count);
  }

  /**
   * @inheritdoc
   */
  public function getFtp() {
    return $this->get('ftp')->value;
  }

  /**
   * @inheritdoc
   */
  public function setFtp($ftp) {
    $this->set('ftp', $ftp);
  }

  /**
   * @inheritdoc
   */
  public function getMeasurementPreference() {
    return $this->get('measurement_preference')->value;
  }

  /**
   * @inheritdoc
   */
  public function setMeasurementPreference($measurement_preference) {
    $this->set('measurement_preference', $measurement_preference);
  }

  /**
   * @inheritdoc
   */
  public function getWeight() {
    return $this->get('weight')->value;
  }

  /**
   * @inheritdoc
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
  }

  /**
   * @inheritdoc
   */
  public function getClubs() {
    return $this->get('clubs')->value;
  }

  /**
   * @inheritdoc
   */
  public function setClubs($clubs) {
    $this->set('clubs', $clubs);
  }

  /**
   * @inheritdoc
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * @inheritdoc
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
  }

  /**
   * @inheritdoc
   */
  public function isPublished() {
    return $this->get('status')->value;
  }

  /**
   * @inheritdoc
   */
  public function setPublished($published) {
    $this->set('status', $published);
  }

  /**
   * @inheritdoc
   */
  public function label() {
    return $this->getFirstName() . ' ' . $this->getLastName();
  }

  /**
   * @inheritdoc
   */
  public function getFirstName() {
    return $this->get('firstname')->value;
  }

  /**
   * @inheritdoc
   */
  public function getLastName() {
    return $this->get('lastname')->value;
  }

  /**
   * @inheritdoc
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * @inheritdoc
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
  }

  /**
   * @inheritdoc
   */
  public function getOwnerId() {
    $uid = $this->get('uid')->getValue();
    return !empty($uid) && isset($uid[0]['target_id']) ? $uid[0]['target_id'] : NULL;
  }

  /**
   * @inheritdoc
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
  }

}
