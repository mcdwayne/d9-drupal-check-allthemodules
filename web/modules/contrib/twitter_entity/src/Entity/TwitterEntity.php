<?php

namespace Drupal\twitter_entity\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Twitter entity entity.
 *
 * @ingroup twitter_entity
 *
 * @ContentEntityType(
 *   id = "twitter_entity",
 *   label = @Translation("Twitter entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\twitter_entity\TwitterEntityListBuilder",
 *     "views_data" = "Drupal\twitter_entity\Entity\TwitterEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\twitter_entity\Form\TwitterEntityForm",
 *       "edit" = "Drupal\twitter_entity\Form\TwitterEntityForm",
 *       "delete" = "Drupal\twitter_entity\Form\TwitterEntityDeleteForm",
 *     },
 *     "access" = "Drupal\twitter_entity\TwitterEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\twitter_entity\TwitterEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "twitter_entity",
 *   admin_permission = "administer twitter entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *     "created" = "created",
 *   },
 *   links = {
 *     "edit-form" = "/admin/content/tweet/{twitter_entity}/edit",
 *     "delete-form" = "/admin/content/tweet/{twitter_entity}/delete",
 *     "collection" = "/admin/content/tweet",
 *   }
 * )
 */
class TwitterEntity extends ContentEntityBase implements TwitterEntityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];

    // Serialize full response if it is array.
    if ($values['full_response'] && (is_array($values['full_response'])) || is_object($values['full_response'])) {
      $values['full_response'] = json_encode($values['full_response']);
    }
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
   * Gets the Tweet ID.
   *
   * @return string
   *   Tweet ID.
   */
  public function getTweetId() {
    return $this->get('tweet_id')->value;
  }

  /**
   * Sets the Tweet ID.
   *
   * @param string $tweet_id
   *   Tweet ID.
   *
   * @return $this
   */
  public function setTweetId($tweet_id) {
    $this->get('tweet_id')->value = $tweet_id;
    return $this;
  }

  /**
   * Gets Twitter user name from witch Tweet was pulled from.
   *
   * @return string
   *   Tweeter user name.
   */
  public function getTwitterUser() {
    return $this->get('twitter_user')->value;
  }

  /**
   * Sets Twitter user name from witch Tweet was pulled from.
   *
   * @param string $twitter_user
   *   Tweeter user name.
   *
   * @return $this
   */
  public function setTwitterUser($twitter_user) {
    $this->get('twitter_user')->value = $twitter_user;
    return $this;
  }

  /**
   * Gets Full JSON response from Twitter API.
   *
   * @return array
   *   Decoded JSON response.
   */
  public function getFullResponse() {
    return json_decode($this->get('full_response')->value);
  }

  /**
   * Sets Full JSON response from Twitter API.
   *
   * @param string $full_response
   *   JSON response from Twitter API.
   *
   * @return $this
   */
  public function setFullResponse($full_response) {
    $this->get('full_response')->value = $full_response;
    return $this;
  }

  /**
   * Gets Tweet media url.
   *
   * @return string
   *   Media url.
   */
  public function getTweetMedia() {
    return $this->get('tweet_media')->value;
  }

  /**
   * Sets Tweet media url.
   *
   * @param string $tweet_media
   *   Media url.
   *
   * @return $this
   */
  public function setTweetMedia($tweet_media) {
    $this->get('tweet_media')->value = $tweet_media;
    return $this;
  }

  /**
   * Gets Tweet text.
   *
   * @return string
   *   Tweet text.
   */
  public function getTweetText() {
    return $this->get('tweet_text')->value;
  }

  /**
   * Sets Tweet text.
   *
   * @param string $tweet_text
   *   Tweet text.
   *
   * @return $this
   */
  public function setTweetText($tweet_text) {
    $this->get('tweet_text')->value = $tweet_text;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Tweet is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean',
        'weight' => 1,
      ])
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 2,
      ]);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    // Custom non standard fields.
    // Tweet id provided by Twitter API.
    $fields['tweet_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tweet id'))
      ->setDescription(t('Tweet id provided by Twitter API.'))
      ->setSettings([
        'max_length' => 255,
      ]);

    // Tweet media.
    $fields['tweet_media'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tweet media'))
      ->setDescription(t('Tweet media url.'))
      ->setSettings([
        'max_length' => 2000,
      ]);

    // Tweet text.
    $fields['tweet_text'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Tweet text'))
      ->setDescription(t('Tweet text.'))
      ->setCardinality(1)
      ->setDisplayOptions('form', [
        'type' => 'text_long',
        'weight' => 0,
      ]);

    // Twitter user name from witch Tweet was pulled from.
    $fields['twitter_user'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Twitter user'))
      ->setDescription(t('Twitter user name from witch Tweet was pulled from.'))
      ->setSettings([
        'max_length' => 255,
      ]);

    // Full JSON response from Twitter API.
    $fields['full_response'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('JSON response'))
      ->setDescription(t('Full JSON response from Twitter API.'))
      ->setSettings([
        'default_value' => '',
      ]);

    return $fields;
  }

  /**
   * Loads one or more tweets by tweet_id provided by Twitter API.
   *
   * @param string $tweet_id
   *   Tweet id.
   * @param bool $full_load
   *   Load full object or not.
   *
   * @return array
   *   Array of Tweets.
   */
  public static function loadByTweetId($tweet_id, $full_load = FALSE) {
    $storage = \Drupal::entityTypeManager()->getStorage('twitter_entity');
    $ids = $storage->getQuery()
      ->condition('tweet_id', $tweet_id)
      ->execute();

    if (!$full_load) {
      return $ids;
    }

    return $storage->loadMultiple($ids);
  }

}
