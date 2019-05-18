<?php

namespace Drupal\rng_contact\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the contact entity.
 *
 * @ContentEntityType(
 *   id = "rng_contact",
 *   label = @Translation("Contact"),
 *   bundle_label = @Translation("Contact type"),
 *   bundle_entity_type = "rng_contact_type",
 *   handlers = {
 *     "storage_schema" = "Drupal\rng_contact\RngContactStorageSchema",
 *     "views_data" = "Drupal\rng_contact\Views\RngContactViewsData",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\rng_contact\AccessControl\RngContactAccessControl",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\rng_contact\Form\RngContactForm",
 *       "add" = "Drupal\rng_contact\Form\RngContactForm",
 *       "edit" = "Drupal\rng_contact\Form\RngContactForm",
 *       "delete" = "Drupal\rng_contact\Form\RngContactDeleteForm",
 *     },
 *   },
 *   base_table = "rng_contact",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "bundle" = "type",
 *   },
 *   links = {
 *     "canonical" = "/rng/contact/{rng_contact}",
 *     "edit-form" = "/rng/contact/{rng_contact}/edit",
 *     "delete-form" = "/rng/contact/{rng_contact}/delete"
 *   },
 *   field_ui_base_route = "entity.rng_contact_type.edit_form",
 * )
 */
class RngContact extends ContentEntityBase implements RngContactInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('owner')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('owner')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('owner', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('owner', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('Label used when referencing this contact. Also used displayed in communications sent to the contact.'))
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);

    $fields['owner'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The owner of this contact.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getCurrentUserId');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the contact was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The last time the contact was edited.'));

    return $fields;
  }

  /**
   * Default value callback for 'owner' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
