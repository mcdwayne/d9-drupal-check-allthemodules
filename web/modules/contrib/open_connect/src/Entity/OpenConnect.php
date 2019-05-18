<?php

namespace Drupal\open_connect\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the open connect entity class.
 *
 * @ContentEntityType(
 *   id = "open_connect",
 *   label = @Translation("Open connect"),
 *   label_collection = @Translation("Open connect"),
 *   label_singular = @Translation("open connect item"),
 *   label_plural = @Translation("open connect items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count open connect item",
 *     plural = "@count open connect items"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\open_connect\OpenConnectStorage",
 *     "storage_schema" = "Drupal\open_connect\OpenConnectStorageSchema",
 *     "list_builder" = "Drupal\open_connect\OpenConnectListBuilder",
 *     "form" = {
 *       "delete" = "Drupal\open_connect\Form\DeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "open_connect",
 *   admin_permission = "administer open_connect",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "openid",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "delete-form" = "/open-connect/{open_connect}/delete",
 *     "collection" = "/admin/config/people/open-connect/accounts"
 *   }
 * )
 *
 */
class OpenConnect extends ContentEntityBase implements OpenConnectInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->get('provider')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setProvider($provider) {
    $this->set('provider', $provider);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOpenid() {
    return $this->get('openid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setOpenid($openid) {
    $this->set('openid', $openid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnionid() {
    return $this->get('unionid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setUnionid($unionid) {
    $this->set('unionid', $unionid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccount(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccountId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['provider'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Provider'))
      ->setDescription(t('The provider plugin id.'))
      ->setRequired(TRUE)
      // In order to work around the InnoDB 191 character limit on utf8mb4 keys,
      // we set the character set for the field to ASCII.
      // see https://www.drupal.org/node/2522098#comment-10081110
      ->setSetting('is_ascii', TRUE);

    $fields['openid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Openid'))
      ->setDescription(t('The openid within a identity provider.'))
      ->setRequired(TRUE)
      ->setSetting('is_ascii', TRUE);

    $fields['unionid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Unionid'))
      ->setDescription(t('The unionid.'))
      ->setDefaultValue('')
      ->setSetting('is_ascii', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The created time.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The changed time.'));

    return $fields;
  }

}
