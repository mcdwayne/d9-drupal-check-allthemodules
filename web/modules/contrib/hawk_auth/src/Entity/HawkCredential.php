<?php

/**
 * @file
 * Contains \Drupal\hawk_auth\Entity\HawkCredential.
 */

namespace Drupal\hawk_auth\Entity;

use Dragooon\Hawk\Credentials\CredentialsInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the HawkCredential entity class.
 *
 * @ContentEntityType(
 *   id = "hawk_credential",
 *   label = @Translation("Hawk Credential"),
 *   handlers = {
 *     "access" = "Drupal\hawk_auth\Entity\HawkCredentialAccessControlHandler",
 *     "storage" = "Drupal\hawk_auth\Entity\HawkCredentialStorage",
 *     "storage_schema" = "Drupal\hawk_auth\Entity\HawkCredentialStorageSchema",
 *     "form" = {
 *        "delete" = "Drupal\hawk_auth\Form\HawkDeleteCredentialForm",
 *        "permissions" = "Drupal\hawk_auth\Form\HawkPermissionsForm",
 *     },
 *   },
 *   base_table = "hawk_credentials",
 *   entity_keys = {
 *     "id" = "cid"
 *   },
 * )
 */
class HawkCredential extends ContentEntityBase implements HawkCredentialInterface {

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($id) {
    $this->set('uid', $id);
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
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeySecret() {
    return $this->get('key_secret')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setKeySecret($key_secret) {
    $this->set('key_secret', $key_secret);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyAlgo() {
    return $this->get('key_algo')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setKeyAlgo($key_algo) {
    $this->set('key_algo', $key_algo);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevokePermissions() {
    return unserialize($this->get('revoke_permissions')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setRevokePermissions(array $permissions) {
    $this->set('revoke_permissions', serialize($permissions));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function revokesPermission($permission) {
    return in_array($permission, $this->getRevokePermissions());
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return $this->getKeySecret();
  }

  /**
   * {@inheritdoc}
   */
  public function algorithm() {
    return $this->getKeyAlgo();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $fields['cid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Credential ID'))
      ->setDescription(t('The credential ID'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('ID of the owner for this credential'))
      ->setSetting('target_type', 'user');

    $fields['key_secret'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Key Secret'))
      ->setDescription(t('Secret for this credential'));

    $fields['key_algo'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Key Algorithm'))
      ->setDescription(t('Encryption algorithm used by requests for this key'));

    $fields['revoke_permissions'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Revoke permissions'))
      ->setDescription(t('Permissions this key revokes from the user'));

    return $fields;
  }

}
