<?php

namespace Drupal\strava_clubs\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Club entity.
 *
 * @ingroup strava
 *
 * @ContentEntityType(
 *   id = "club",
 *   label = @Translation("Club"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\strava_clubs\ClubListBuilder",
 *     "views_data" = "Drupal\strava_clubs\Entity\ClubViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\strava_clubs\Form\ClubForm",
 *       "add" = "Drupal\strava_clubs\Form\ClubForm",
 *       "edit" = "Drupal\strava_clubs\Form\ClubForm",
 *       "delete" = "Drupal\strava_clubs\Form\ClubDeleteForm",
 *       "refresh" = "Drupal\strava_clubs\Form\ClubRefreshForm",
 *     },
 *     "access" = "Drupal\strava_clubs\ClubAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\strava_clubs\ClubHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "club",
 *   admin_permission = "administer club entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/strava/clubs/club/{club}",
 *     "add-form" = "/strava/clubs/club/add",
 *     "edit-form" = "/strava/clubs/club/{club}/edit",
 *     "delete-form" = "/strava/clubs/club/{club}/delete",
 *     "collection" = "/strava/clubs/club",
 *   },
 *   field_ui_base_route = "club.settings"
 * )
 */
class Club extends ContentEntityBase implements ClubInterface {

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
      ->setLabel(t('Club id'))
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode']->setLabel(t('Language code'))
      ->setDescription(t('The entity language code.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setSettings([
        'max_length' => 128,
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

    $fields['profile'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Profile picture url'))
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
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cover_photo'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Cover photo url'))
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
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
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

    $fields['sport_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sport type'))
      ->setDescription(t('May take one of the following values: cycling, running, triathlon, other.'))
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
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['member_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Member count'))
      ->setTranslatable(FALSE)
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Url'))
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
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Club is published.'))
      ->setDefaultValue(TRUE);

    return $fields;
  }

  /**
   * Get a list of members for this club.
   *
   * @return array
   */
  public function getClubMembers() {
    $query = \Drupal::database()
      ->select('athlete__clubs', 'ac')
      ->fields('ac', ['entity_id'])
      ->condition('clubs_target_id', $this->getId());
    $result = $query->execute();

    return $result->fetchCol();
  }

  /**
   * Gets the club id.
   *
   * @return int
   */
  public function getId() {
    return $this->get('id')->value;
  }

  /**
   * Sets the club id.
   *
   * @param int $id
   */
  public function setId($id) {
    $this->set('id', $id);
  }

  /**
   * Gets the resource state.
   *
   * @return int
   */
  public function getResourceState() {
    return $this->get('resource_state')->value;
  }

  /**
   * Sets the resource state
   *
   * @param int $resource_state
   */
  public function setResourceState($resource_state) {
    $this->set('resource_state', $resource_state);
  }

  /**
   * Gets the club name.
   *
   * @return string
   *   Name of the Club.
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * Sets the Club name.
   *
   * @param string $name
   */
  public function setName($name) {
    $this->set('name', $name);
  }

  /**
   * Gets the profile.
   *
   * @return int
   */
  public function getProfile() {
    return $this->get('profile')->value;
  }

  /**
   * Sets the profile
   *
   * @param string $profile
   */
  public function setProfile($profile) {
    $this->set('profile', $profile);
  }

  /**
   * Gets the cover photo.
   *
   * @return string
   */
  public function getCoverPhoto() {
    return $this->get('cover_photo')->value;
  }

  /**
   * Sets the cover photo
   *
   * @param string $cover_photo
   */
  public function setCoverPhoto($cover_photo) {
    $this->set('cover_photo', $cover_photo);
  }

  /**
   * Gets the description.
   *
   * @return string
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * Sets the description
   *
   * @param string $description
   */
  public function setDescription($description) {
    $this->set('description', $description);
  }

  /**
   * Gets the sport type
   *
   * @return string
   */
  public function getSportType() {
    return $this->get('sport_type')->value;
  }

  /**
   * Sets the sport type
   *
   * @param string $sport_type
   */
  public function setSportType($sport_type) {
    $this->set('sport_type', $sport_type);
  }

  /**
   * Gets the city.
   *
   * @return string
   */
  public function getCity() {
    return $this->get('city')->value;
  }

  /**
   * Sets the city
   *
   * @param string $city
   */
  public function setCity($city) {
    $this->set('city', $city);
  }

  /**
   * Gets the state.
   *
   * @return string
   */
  public function getState() {
    return $this->get('state')->value;
  }

  /**
   * Sets the state
   *
   * @param string $state
   */
  public function setState($state) {
    $this->set('state', $state);
  }

  /**
   * Gets the country.
   *
   * @return string
   */
  public function getCountry() {
    return $this->get('country')->value;
  }

  /**
   * Sets the cover country
   *
   * @param string $country
   */
  public function setCountry($country) {
    $this->set('country', $country);
  }

  /**
   * Gets the member count.
   *
   * @return integer
   */
  public function getMemberCount() {
    return $this->get('member_count')->value;
  }

  /**
   * Sets the member count
   *
   * @param int $member_count
   */
  public function setMemberCount($member_count) {
    $this->set('member_count', $member_count);
  }

  /**
   * Gets the url
   *
   * @return string
   */
  public function getUrl() {
    return $this->get('url')->value;
  }

  /**
   * Sets the  url
   *
   * @param string $url
   */
  public function setUrl($url) {
    $this->set('url', $url);
  }

  /**
   * Returns the Club published status indicator.
   *
   * Unpublished Club are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Club is published.
   */
  public function isPublished() {
    return $this->get('status')->value;
  }

  /**
   * Sets the published status of a Club.
   *
   * @param bool $published
   *   TRUE to set this Club to published, FALSE to set it to unpublished.
   */
  public function setPublished($published) {
    $this->set('status', $published);
  }

}
