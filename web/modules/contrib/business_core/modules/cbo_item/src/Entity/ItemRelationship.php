<?php

namespace Drupal\cbo_item\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\cbo_item\ItemRelationshipInterface;

/**
 * Defines the item relationship entity class.
 *
 * @ContentEntityType(
 *   id = "item_relationship",
 *   label = @Translation("Item relationship"),
 *   base_table = "item_relationship",
 *   entity_keys = {
 *     "id" = "rid",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class ItemRelationship extends ContentEntityBase implements ItemRelationshipInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['item'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Item'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'item')
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

    $fields['relationship_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Relationship Type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'related' => 'Related: in a non-specific way',
        'substitute' => 'Substitute: item is a substitute for another',
        'cross_sell' => 'Cross-Sell: item may be sold in lieu of another item',
        'up_sell' => 'Up-Sell: a newer version of the item can be sold in place of the older item',
        'service' => 'Service: service items for a repairable item',
        'prerequisite' => 'Prerequisite: item as a requirement to possessing the other item',
        'collateral' => 'Collateral',
        'superceded' => 'Superceded: item has replaced another item that is no longer available',
        'complimentary' => 'Complimentary: if purchases the item, the other item is received for free',
        'impact' => 'Impact: relate items to each other but only under special conditions',
        'conflict' => 'Conflict: items may never be used together',
        'mandatory_charge' => 'Mandatory Change: a mandatory charge if the customer purchases both items',
        'optional_charge' => 'Optional Charge: an optional charge if the customer purchases both items',
        'promotional_upgrade' => 'Promotional Upgrade: upgrade from one item to another item without an additional charge',
        'split' => 'Split: split support for an item',
        'merge' => 'Merge: enables rules based consolidation of contracts',
        'migration' => 'Migration',
        'repair_to' => 'Repair to',
      ])
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
      ->setDescription(t('The timestamp that the cbo_item was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The timestamp that the cbo_item was last changed.'));

    return $fields;
  }

}
