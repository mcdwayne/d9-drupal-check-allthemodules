<?php

namespace Drupal\smallads\Entity;

use Drupal\smallads\Entity\SmalladInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the Smallad entity.
 *
 * @ContentEntityType(
 *   id = "smallad",
 *   label = @Translation("Small Ad"),
 *   bundle_label = @Translation("Small ad type"),
 *   label_collection = @Translation("Ads"),
 *   handlers = {
 *     "storage" = "Drupal\smallads\SmalladStorage",
 *     "view_builder" = "Drupal\smallads\SmalladViewBuilder",
 *     "list_builder" = "Drupal\smallads\SmalladListBuilder",
 *     "access" = "Drupal\smallads\SmalladAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\smallads\Form\SmalladEdit",
 *       "edit" = "Drupal\smallads\Form\SmalladEdit",
 *       "delete" = "Drupal\smallads\Form\SmalladDeleteConfirm",
 *     },
 *     "views_data" = "Drupal\smallads\SmalladViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "acccess administration pages",
 *   base_table = "smallad",
 *   data_table = "smallad_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "smid",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "bundle" = "type",
 *     "uid" = "uid",
 *   },
 *   bundle_entity_type = "smallad_type",
 *   field_ui_base_route = "entity.smallad_type.edit_form",
 *   translatable = TRUE,
 *   links = {
 *     "canonical" = "/ad/{smallad}",
 *     "add-form" = "/ad/add/{smallad_type}",
 *     "edit-form" = "/ad/{smallad}/edit",
 *     "delete-form" = "/ad/{smallad}/delete",
 *     "collection" = "/admin/content/smallads"
 *   }
 * )
 */
class Smallad extends ContentEntityBase implements SmalladInterface, EntityOwnerInterface, EntityChangedInterface, EntityPublishedInterface {

  use StringTranslationTrait;
  use EntityChangedTrait;

  /**
   * Constructor.
   *
   * @note There seems to be no way to inject anything here.
   *
   * @see SqlContentEntityStorage::mapFromStorageRecords
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = array()) {
    parent::__construct($values, $entity_type, $bundle, $translations);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The node language code.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 2,
      ]);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type of small ad'))
      ->setDescription(t('E.g. offer, want'))
      ->setSetting('target_type', 'smallad_type')
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('One line description'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['body'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Tell the story behind this ad.'))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setSettings(['default_value' => ''])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Posted by'))
      ->setDescription(t('The owner of the small ad'))
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
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
          'placeholder' => '',
        ],
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the ad was created.'))
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Last changed'))
      ->setDescription(t('When the ad was last saved.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      //->setInitialValue('created')
      ->setDisplayConfigurable('view', TRUE);

    $fields['scope'] = BaseFieldDefinition::create('smallad_scope')
      ->setLabel(t('Visibility'))
      ->setDescription(t('How widely this ad can be seen'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDefaultValueCallback('smalladsDefaultScope')
      ->setRequired(TRUE);

    // This depends on the datetime module.
    $fields['expires'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expires on'))
      ->setDescription(t('Visibility reverts to private on this date'))
      ->setRevisionable(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDefaultValueCallback('Drupal\smallads\Entity\Smallad::expiresDefault')
      ->setTranslatable(FALSE)
      ->setRequired(TRUE);

    $fields['categories'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Category'))
      ->setDescription(t('The category or categories of the ad'))
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['categories']])
      ->setRequired(TRUE)
      ->setCardinality(3)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['directexchange'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Barter'))
      ->setDescription(t('See description field for requested reciprocation.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['indirectexchange'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Community Currency'))
      ->setDescription(t('Community currency is acceptable.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['money'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Money'))
      ->setDescription(t('Some or all of the payment will be in money.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
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
  public static function expiresDefault() {
    $interval = \Drupal::config('smallads.settings')->get('default_expiry');
    // $values['expires'] = ['year'=> 1980, 'month'=> 8, 'day' => 8];
    // $values['expires'] = '1999-9-9';//still not working...
    // return strtotime(\Drupal::config('smallads.settings')
    // ->get('default_expiry'))
    $dateTime = DrupalDateTime::createFromTimestamp(strtotime($interval));
    return strtotime($dateTime);
  }

  /**
   * {@inheritdoc}
   */
  public function expire() {
    // There might be an option on notification somewhere?
    $this->scope->value = SmalladInterface::SCOPE_PRIVATE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    if (isset($this->get('created')->value)) {
      return $this->get('created')->value;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished(){
    return $this->scope->value > SmalladInterface::SCOPE_PRIVATE;
  }

  /**
   * {@inheritdoc}
   *
   * This could be problematic in conjunction with solsearch
   */
  public function setPublished($published = NULL) {
    $this->scope->value = SmalladInterface::SCOPE_SITE;
  }

  /**
   * {@inheritdoc}
   */
  public function setUnPublished($published = NULL){
    return $this->expire();
  }
}

