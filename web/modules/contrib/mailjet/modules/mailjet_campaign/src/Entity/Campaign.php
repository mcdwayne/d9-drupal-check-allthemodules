<?php

namespace Drupal\mailjet_campaign\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\mailjet_campaign\CampaignInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use MailJet\MailJet;

/**
 * Defines the Campaign entity.
 *
 * @ingroup campaign_entity
 *
 *
 * @ContentEntityType(
 *   id = "campaign_entity",
 *   label = @Translation("Campaign"),
 *   handlers = {
 *     *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "mailjet_campign",
 *   admin_permission = "administer modules",
 *   entity_keys = {
 *     "id" = "campaign_id",
 *     "uuid" = "uuid"
 *   },
 * )
 *
 */
class Campaign extends ContentEntityBase implements CampaignInterface  {

  use EntityChangedTrait;

  /*
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
    $fields['campaign_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Campaign.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Campaign.'))
      ->setReadOnly(TRUE);

    // Name field for the contact.
    // We set display options for the view as well as the form.
    // Users with correct privileges can change the view and edit configuration.

    /*
        $fields['order_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('ORDER ID'))
            ->setDescription(t('ORDER ID of commerce order'))
            ->setSettings([
              'max_length' => 255,
              'text_processing' => 0,
            ])
            // Set no default value.
            ->setDefaultValue(NULL)
            ->setDisplayOptions('view', [
              'label' => 'above',
              'type' => 'string',
              'weight' => 1,
            ])
            ->setDisplayOptions('form', [
              'type' => 'string_textfield',
              'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);
    */

    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order ID'))
      ->setDescription(t('ORDER ID'))
      ->setSettings([
        'target_type' => 'commerce_order',
        'default_value' => 0,
      ]);


    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Campaign Name'))
      ->setDescription('')
      ->setSettings([
        'max_length' => 800,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['camp_id_mailjet'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Mailjet Campaign ID'))
      ->setDescription('')
      ->setSettings([
        'max_length' => 800,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of  Campaign.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the  Campaign entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the  Campaign entity was last edited.'));

    return $fields;
  }

}
