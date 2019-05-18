<?php

namespace Drupal\search_api_synonym\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\search_api_synonym\SynonymInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Synonym entity.
 *
 * @ingroup search_api_synonym
 *
 * @ContentEntityType(
 *   id = "search_api_synonym",
 *   label = @Translation("Search API Synonym"),
 *   handlers = {
 *     "views_data" = "Drupal\search_api_synonym\SynonymViewsData",
 *     "list_builder" = "Drupal\search_api_synonym\SynonymListBuilder",
 *     "form" = {
 *       "add" = "Drupal\search_api_synonym\Form\SynonymForm",
 *       "edit" = "Drupal\search_api_synonym\Form\SynonymForm",
 *       "delete" = "Drupal\search_api_synonym\Form\SynonymDeleteForm",
 *     },
 *   },
 *   base_table = "search_api_synonym",
 *   data_table = "search_api_synonym_field_data",
 *   admin_permission = "administer search api synonyms",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "sid",
 *     "label" = "word",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/search-api-synonyms/{search_api_synonym}",
 *     "edit-form" = "/admin/config/search/search-api-synonyms/{search_api_synonym}/edit",
 *     "delete-form" = "/admin/config/search/search-api-synonyms/{search_api_synonym}/delete",
 *     "collection" = "/admin/config/search/search-api-synonyms"
 *   }
 * )
 */
class Synonym extends ContentEntityBase implements SynonymInterface {

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
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->set('type', $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWord() {
    return $this->get('word')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWord($word) {
    $this->set('word', $word);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSynonyms() {
    return $this->get('synonyms')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSynonymsFormatted() {
    $synonyms = $this->get('synonyms')->value;
    $synonyms = str_replace(',', ', ', $synonyms);
    return trim($synonyms);
  }

  /**
   * {@inheritdoc}
   */
  public function setSynonyms($synonyms) {
    $this->set('synonyms', $synonyms);
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
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return (bool) $this->get('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setActive($active) {
    $this->set('status', $active ? SYNONYM_ACTIVE : SYNONYM_NOT_ACTIVE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['sid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Synonym entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Synonym entity.'))
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Synonym entity.'))
      ->setSettings([
        'target_type' => 'user',
        'handler' => 'default',
        'required' => TRUE,
      ])
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Type'))
      ->setDescription(t('The type of synonym.'))
      ->setSettings([
        'max_length' => 50,
        'allowed_values' => array('synonym' => 'Synonym', 'spelling_error' => 'Spelling error'),
      ])
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['word'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Word'))
      ->setDescription(t('The word we are defining synonyms for.'))
      ->setSettings([
        'max_length' => 128,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE)
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

    $fields['synonyms'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Synonyms'))
      ->setDescription(t('The synonyms to the word. Separate multiple by comma.'))
      ->setSettings([
        'max_length' => 1024,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activate synonym'))
      ->setDescription(t('Only active synonyms will be used.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The language for the Synonym entity.'))
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
