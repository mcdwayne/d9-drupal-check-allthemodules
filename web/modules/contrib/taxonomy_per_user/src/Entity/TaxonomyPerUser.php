<?php

namespace Drupal\taxonomy_per_user\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\taxonomy_per_user\TaxonomyPerUserInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the TaxonomyPerUser entity.
 *
 * @ingroup taxonomy_per_user
 * *
 * @ContentEntityType(
 *   id = "taxonomy_per_user",
 *   label = @Translation("Taxonomy Per User entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\taxonomy_per_user\Entity\Controller\TaxonomyPerUserListBuilder",
 *     "form" = {
 *       "add" = "Drupal\taxonomy_per_user\Form\TaxonomyPerUserForm",
 *       "edit" = "Drupal\taxonomy_per_user\Form\TaxonomyPerUserForm",
 *       "delete" = "Drupal\taxonomy_per_user\Form\TaxonomyPerUserDeleteForm",
 *     },
 *     "access" = "Drupal\taxonomy_per_user\TaxonomyPerUserAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "taxonomy_per_user",
 *   admin_permission = "administer taxonomy_per_user entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/taxonomy_per_user/{taxonomy_per_user}",
 *     "edit-form" = "/taxonomy_per_user/{taxonomy_per_user}/edit",
 *     "delete-form" = "/taxonomy_per_user/{taxonomy_per_user}/delete",
 *      "collection" = "/admin/structure/taxonomy_per_user/overview",
 *   },
 *
 * )
 */
class TaxonomyPerUser extends ContentEntityBase implements TaxonomyPerUserInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
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
  public function getChangedTime() {
    return $this->get('changed')->value;
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
  public function getLabel() {
    return $this->get('label')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getVocabulary() {
    return $this->get('target_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getVocabularyId() {
    return $this->get('target_id')->target_id;
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the TaxonomyPerUser entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the TaxonomyPerUser entity.'))
      ->setReadOnly(TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the TaxonomyPerUser entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Name'))
      ->setDescription(t('The Name of the associated user.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    // Borrowed this logic from the Comment module.
    // Warning! May change in the future: https://www.drupal.org/node/2346347
    // The target type is set to a config entity to force a string field
    // for the entity ID.
    // @see https://www.drupal.org/node/1757452.
    $fields['target_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Vocabulary'))
      ->setDescription(t('The vocabulary to add to the taxonomy_per_user.'))
      ->setSetting('target_type', 'taxonomy_vocabulary')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'taxonomy_vocabulary',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -4,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
