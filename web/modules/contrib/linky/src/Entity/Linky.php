<?php

namespace Drupal\linky\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\link\LinkItemInterface;
use Drupal\linky\LinkyInterface;
use Drupal\linky\Url;
use Drupal\user\UserInterface;

/**
 * Defines the Linky entity.
 *
 * @ingroup linky
 *
 * @ContentEntityType(
 *   id = "linky",
 *   label = @Translation("Managed Link"),
 *   handlers = {
 *     "view_builder" = "Drupal\linky\LinkyEntityViewBuilder",
 *     "list_builder" = "Drupal\linky\LinkyListBuilder",
 *     "views_data" = "Drupal\linky\Entity\LinkyViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\linky\Form\LinkyForm",
 *       "add" = "Drupal\linky\Form\LinkyForm",
 *       "edit" = "Drupal\linky\Form\LinkyForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\linky\LinkyAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\linky\LinkyHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "linky",
 *   admin_permission = "administer linky entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "link__title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/linky/{linky}",
 *     "add-form" = "/admin/content/linky/add",
 *     "edit-form" = "/admin/content/linky/{linky}/edit",
 *     "delete-form" = "/admin/content/linky/{linky}/delete",
 *     "collection" = "/admin/content/linky",
 *   },
 *   field_ui_base_route = "entity.linky.admin"
 * )
 */
class Linky extends ContentEntityBase implements LinkyInterface {
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
  public function getLastCheckedTime() {
    return $this->get('checked')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastCheckedTime($timestamp) {
    $this->get('checked')->value = $timestamp;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Managed Link entity.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Managed Link entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Managed Link entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
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

    $fields['link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Link'))
      ->setDescription(t('The location this managed link points to.'))
      ->setRequired(TRUE)
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_EXTERNAL,
        'title' => DRUPAL_REQUIRED,
      ])
      ->setDisplayOptions('view', [
        'type' => 'link',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => -2,
      ]);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Managed Link entity.'))
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

    $fields['checked'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last checked'))
      ->setDescription(t('The time that the link was last checked.'))
      ->setDefaultValue(0);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->link->title . ' (' . $this->link->uri . ')';
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    $internalCanonical = parent::toUrl($rel, $options);
    if ($rel === 'canonical') {
      $options['linky_entity_canonical'] = $internalCanonical;
      return Url::fromUri($this->link->uri, $options);
    }
    return $internalCanonical;
  }

  /**
   * {@inheritdoc}
   */
  public function toLink($text = NULL, $rel = 'canonical', array $options = []) {
    if (!isset($text)) {
      return parent::toLink($this->link->title, $rel, $options);
    }
    return parent::toLink($text, $rel, $options);
  }

}
