<?php

namespace Drupal\quick_code\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\quick_code\QuickCodeInterface;

/**
 * Defines the quick_code entity.
 *
 * @ContentEntityType(
 *   id = "quick_code",
 *   label = @Translation("Quick code", context="Entity"),
 *   label_collection = @Translation("Quick codes"),
 *   bundle_label = @Translation("Quick code type"),
 *   handlers = {
 *     "storage" = "Drupal\quick_code\QuickCodeStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\quick_code\QuickCodeAccessControlHandler",
 *     "views_data" = "Drupal\quick_code\QuickCodeViewsData",
 *     "form" = {
 *       "default" = "Drupal\quick_code\QuickCodeForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "quick_code",
 *   entity_keys = {
 *     "id" = "qid",
 *     "bundle" = "type",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   bundle_entity_type = "quick_code_type",
 *   field_ui_base_route = "entity.quick_code_type.canonical",
 *   common_reference_target = TRUE,
 *   links = {
 *     "canonical" = "/admin/quick_code/{quick_code}",
 *     "delete-form" = "/admin/quick_code/{quick_code}/delete",
 *     "edit-form" = "/admin/quick_code/{quick_code}/edit",
 *     "add-page" = "/admin/quick_code/add",
 *     "add-form" = "/admin/quick_code/add/{quick_code_type}",
 *     "collection" = "/admin/quick_code",
 *   },
 *   permission_granularity = "bundle"
 * )
 */
class QuickCode extends ContentEntityBase implements QuickCodeInterface {

  use EntityChangedTrait;

  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (!$this->original || $this->label->value != $this->original->label->value) {
      $quick_code = \Drupal::service('quick_code');
      $this->quick_code->value = $quick_code->transliterate($this->label->value);
    }
  }

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['quick_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Quick code', [], ['context' => 'Short code']))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['effective_dates'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Effective Dates'))
      ->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE)
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'type' => 'daterange_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'daterange_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['code'] = BaseFieldDefinition::create('code')
      ->setLabel(t('Code'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent'))
      ->setDescription(t('The parent quick code.'))
      ->setSetting('target_type', 'quick_code')
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this quick code in relation to other quick codes.'))
      ->setDefaultValue(0);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the quick code was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the quick code was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    if ($type = QuickCodeType::load($bundle)) {
      $fields['code'] = clone $base_field_definitions['code'];
      $fields['code']->setSetting('encoding_rules', $type->getEncodingRules());
      return $fields;
    }
    return [];
  }

}
