<?php
namespace Drupal\migrate_qa\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\link\LinkItemInterface;
use Drupal\migrate_qa\Plugin\Field\FieldType\FlagDetailsCount;

/**
 * Migrate QA Flag.
 *
 * @ContentEntityType(
 *   id = "migrate_qa_flag",
 *   label = @Translation("Migrate QA Flag"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\migrate_qa\Controller\FlagListBuilder",
 *     "views_data" = "Drupal\migrate_qa\Entity\FlagViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\migrate_qa\FlagAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "migrate_qa_flag",
 *   revision_table = "migrate_qa_flag_revision",
 *   admin_permission = "administer migrate_qa_flag entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/migrate-qa-flag/{migrate_qa_flag}",
 *     "add-form" = "/migrate-qa-flag/add",
 *     "edit-form" = "/migrate-qa-flag/{migrate_qa_flag}/edit",
 *     "delete-form" = "/migrate-qa-flag/{migrate_qa_flag}/delete",
 *     "collection" = "/admin/structure/migrate-qa/flag",
 *   },
 *   fieldable = TRUE,
 *   field_ui_base_route = "migrate_qa_flag.settings",
 * )
 */
class Flag extends ContentEntityBase implements FlagInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    $flag_type = $this->get('flag_type');
    $flag_type_name = '';
    if (!$flag_type->isEmpty()) {
      $flag_type_name = $flag_type->referencedEntities()[0]->label();
    }

    $replacements = [
      '@original_id' => $this->get('original_id')->value,
      '@field' => $this->get('field')->value,
      '@type' => $flag_type_name,
    ];

    return new TranslatableMarkup('@original_id / @field / @type', $replacements);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Add fields defined by the parent.
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add Flag field.
    $fields['tracker'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tracker'))
      ->setDescription('The QA Tracker entity related to this flag')
      ->setCardinality(1)
      ->setSettings([
        'target_type' => 'migrate_qa_tracker',
      ])
      ->setSetting('handler', 'default:migrate_qa_tracker')
      ->setSetting('handler_settings', [
        'target_bundles' => NULL,
        'auto_create' => FALSE,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -8,
      ])
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_entity_view',
        'weight' => -8,
        'label' => 'above',
        'settings' => [
          'link' => TRUE,
          'view_mode' => 'default',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Add the Original ID field.
    $fields['original_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Original ID'))
      ->setDescription(t('The original ID of the entity being migrated.'))
      ->setSettings([
        'max_length' => 1024,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Add the Original URL field.
    $fields['original_url'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Original URL'))
      ->setDescription(t('The original URL of the entity being migrated'))
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_EXTERNAL,
        'title' => DRUPAL_DISABLED,
      ])
      ->setDisplayOptions('view', [
        'type' => 'link',
        'weight' => -9,
        'label' => 'inline',
        'settings' => [
          'trim_length' => 200,
          'target' => '_blank',
          'url_only' => FALSE,
          'url_plain' => FALSE,
          'rel' => '0',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => -9,
        'settings' => [
          'placeholder_url' => 'URL to original content',
          'placeholder_title' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Add the Field field, yes, the Field field.
    $fields['field'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Field'))
      ->setDescription(t('Field where that caused the flag, e.g. body field, date field, etc.'))
      ->setSettings([
        'max_length' => 1024,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Add the Details field.
    $fields['details'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Details'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Add the Details Count computed field.
    $fields['details_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Details Count'))
      ->setCardinality(1)
      ->setComputed(TRUE)
      ->setClass(FlagDetailsCount::class)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
