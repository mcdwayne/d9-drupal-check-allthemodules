<?php

namespace Drupal\wallet\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;
use Drupal\wallet\WalletCurrencyInterface;

/**
 * Defines the Wallet Currency entity.
 *
 * @ingroup wallet_currency
 *
 *
 *
 * There are many more properties to be used in an entity type definition. For
 * a complete overview, please refer to the '\Drupal\Core\Entity\EntityType'
 * class definition.
 *
 * The following construct is the actual definition of the entity type which
 * is read and cached. Don't forget to clear cache after changes.
 *
 * @ContentEntityType(
 *   id = "wallet_currency",
 *   label = @Translation("Wallet Currency"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\wallet\Form\WalletCurrencyForm",
 *       "edit" = "Drupal\wallet\Form\WalletCurrencyForm",
 *       "delete" = "Drupal\wallet\Form\WalletCurrencyDeleteForm",
 *     },
 *     "access" = "Drupal\wallet\WalletAccessControlHandler",
 *   },
 *   base_table = "wallet_currency",
 *   admin_permission = "administer wallet currency",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/wallet_currency/{wallet_currency}",
 *     "edit-form" = "/wallet_currency/{wallet_currency}/edit",
 *     "delete-form" = "/wallet_currency/{wallet_currency}/delete",
 *     "collection" = "/wallet_currency/list"
 *   },
 *   field_ui_base_route = "wallet.wallet_currency_settings",
 * )
 *
 * The 'links' above are defined by their path. For core to find the corresponding
 * route, the route name must follow the correct pattern:
 *
 * entity.<entity-name>.<link-name> (replace dashes with underscores)
 *
 * See routing file above for the corresponding implementation
 *
 * The 'Contact' class defines methods and fields for the contact entity.
 *
 * Being derived from the ContentEntityBase class, we can override the methods
 * we want. In our case we want to provide access to the standard fields about
 * creation and changed time stamps.
 *
 * Our interface (see ContactInterface) also exposes the EntityOwnerInterface.
 * This allows us to provide methods for setting and providing ownership
 * information.
 *
 * The most important part is the definitions of the field properties for this
 * entity type. These are of the same type as fields added through the GUI, but
 * they can by changed in code. In the definition we can define if the user with
 * the rights privileges can influence the presentation (view, edit) of each
 * field.
 */
class WalletCurrency extends ContentEntityBase implements WalletCurrencyInterface {

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array('user_id' => \Drupal::currentUser()->id());
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
  public function setChangedTime($timestamp) {
    $this->set('changed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTimeAcrossTranslations() {
    $changed = $this->getUntranslated()->getChangedTime();
    foreach ($this->getTranslationLanguages(FALSE) as $language) {
      $translation_changed = $this->getTranslation($language->getId())->getChangedTime();
      $changed = max($translation_changed, $changed);
    }

    return $changed;
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
   * Field name, type and size determine the table  structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = array();

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Wallet Currency.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Wallet Currency.'))
      ->setReadOnly(TRUE);
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Wallet Currency.'))
      ->setSettings(array('default_value' => '', 'max_length' => 255, 'text_processing' => 0,))
      ->setDisplayOptions('view', array('label' => 'above', 'type' => 'string', 'weight' => -6))
      ->setDisplayOptions('form', array('type' => 'string', 'weight' => -6))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);
    $fields['short_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Short Name'))
      ->setDescription(t('The short name of the Wallet Currency.'))
      ->setSettings(array('default_value' => '', 'max_length' => 25, 'text_processing' => 0,))
      ->setDisplayOptions('view', array('label' => 'above', 'type' => 'string', 'weight' => -5))
      ->setDisplayOptions('form', array('type' => 'string', 'weight' => -5))
      ->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);
    $fields['position'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Position'))
      ->setDescription(t('The position of the Wallet Currency.'))
      ->setSettings(array('allowed_values' => array('Before' => 'Before', 'After' => 'After')))
      ->setDisplayOptions('view', array('label' => 'above', 'type' => 'string', 'weight' => -4))
      ->setDisplayOptions('form', array('type' => 'options_select', 'weight' => -4))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);
    $fields['langcode'] = BaseFieldDefinition::create('language')->setLabel(t('Language code'))->setDescription(t('The language code of Contact entity.'));
    $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Created'))->setDescription(t('The time that the entity was created.'));
    $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Changed'))->setDescription(t('The time that the entity was last edited.'));
    return $fields;
  }

}