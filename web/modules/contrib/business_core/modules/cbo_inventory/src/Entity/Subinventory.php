<?php

namespace Drupal\cbo_inventory\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\cbo_inventory\SubinventoryInterface;

/**
 * Defines the subinventory entity class.
 *
 * @ContentEntityType(
 *   id = "subinventory",
 *   label = @Translation("Subinventory"),
 *   bundle_label = @Translation("Subinventory type"),
 *   handlers = {
 *     "storage" = "Drupal\cbo_inventory\SubinventoryStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\cbo_inventory\SubinventoryAccessControlHandler",
 *     "views_data" = "Drupal\cbo_inventory\SubinventoryViewsData",
 *     "form" = {
 *       "default" = "Drupal\cbo_inventory\SubinventoryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   base_table = "subinventory",
 *   entity_keys = {
 *     "id" = "sid",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   bundle_entity_type = "subinventory_type",
 *   field_ui_base_route = "entity.subinventory_type.edit_form",
 *   admin_permission = "administer subinventories",
 *   links = {
 *     "add-page" = "/admin/subinventory/add",
 *     "add-form" = "/admin/subinventory/add/{subinventory_type}",
 *     "canonical" = "/admin/subinventory/{subinventory}",
 *     "edit-form" = "/admin/subinventory/{subinventory}/edit",
 *     "delete-form" = "/admin/subinventory/{subinventory}/delete",
 *     "collection" = "/admin/subinventory",
 *   }
 * )
 */
class Subinventory extends ContentEntityBase implements SubinventoryInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['organization'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Organization'))
      ->setDescription(t('The organization which this subinventory belongs to.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'organization')
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -3,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['quantity_tracked'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Quantity Tracked'))
      ->setDescription(t('Whether each transaction for this subinventory updates the quantity on hand for the subinventory.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['asset_subinventory'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Asset subinventory'))
      ->setDescription(t('Whether to maintain the value of this subinventory on the balance sheet.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['depreciable'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Depreciable'))
      ->setDescription(t('Whether this subinventory is depreciable.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['locator_control'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Locator Control'))
      ->setDescription(t('Type of locator control.'))
      ->setSetting('allowed_values', [
        'none' => 'None',
        'prespecified' => 'Prespecified',
        'dynamic_entry' => 'Dynamic entry',
        'item_level' => 'Item level',
      ])
      ->setDefaultValue('none')
      ->setDisplayOptions('view', [
        'type' => 'list_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['picking_order'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Picking Order'))
      ->setDescription(t('Picking order value for sequencing picking tasks.'))
      ->setDisplayOptions('view', [
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['dropping_order'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Dropping Order'))
      ->setDescription(t('Numeric dropping order value.'))
      ->setDisplayOptions('view', [
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['source_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Source Type'))
      ->setDescription(t('Source type for item replenishment.'))
      ->setSetting('allowed_values', [
        'inventory' => 'Inventory: Replenish items internally, from another organization.',
        'supplier' => 'Supplier: Replenish items externally, from a supplier.',
        'subinventory' => 'Subinventory: Replenish items from another subinventory in the same inventory organization.',
      ])
      ->setDisplayOptions('view', [
        'type' => 'list_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['source_organization'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Source Organization'))
      ->setDescription(t('The organization used to replenish items in this subinventory.'))
      ->setSetting('target_type', 'organization')
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -3,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['source_subinventory'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Source Subinventory'))
      ->setDescription(t('The subinventory for replenishing items.'))
      ->setSetting('target_type', 'subinventory')
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -3,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The timestamp that the bom was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The timestamp that the bom was last changed.'));

    return $fields;
  }

}
