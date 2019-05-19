<?php

namespace Drupal\vib\Entity;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the vib link entity class.
 *
 * @ContentEntityType(
 *   id = "vib_link",
 *   label = @Translation("'View in browser' link"),
 *   base_table = "vib_link",
 *   entity_keys = {
 *     "id" = "id"
 *   }
 * )
 */
class VibLink extends ContentEntityBase implements VibLinkInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['token' => Crypt::hashBase64(time())];
  }

  /**
   * {@inheritdoc}
   */
  public function getToken() {
    return $this->get('token')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeletedTime() {
    return $this->get('deleted')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDeletedTime($timestamp) {
    $this->set('deleted', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmailContent() {
    return $this->get('email_body')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmailContent($text) {
    $this->set('email_body', $text);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getlibrary() {
    return $this->get('library')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLibrary($name) {
    $this->set('library', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token'))
      ->setDescription(t('A token that allows any user to view a mail in browser.'))
      ->setRequired(TRUE);

    $fields['deleted'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Deleted'))
      ->setDescription(t('The timestamp that the entity will be removed.'));

    $fields['email_body'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Email body'))
      ->setDescription(t('The email body content.'));

    $fields['library'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Library'))
      ->setDescription(t('The drupal library to attach to the browser view of the email.'));

    return $fields;
  }

}
