<?php

namespace Drupal\commerce_inventory\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference_quantity_table' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_inventory_quantity_table",
 *   label = @Translation("Table"),
 *   description = @Translation("Display the label of the referenced entities with quantity in a table."),
 *   field_types = {
 *     "entity_reference_inventory_quantity"
 *   }
 * )
 */
class EntityReferenceInventoryQuantityTableFormatter extends EntityReferenceLabelFormatter {

  /**
   * The target entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The Entity Type for the target id.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The target entity type definition.
   */
  protected function getTargetEntityType() {
    if (is_null($this->entityType)) {
      $this->entityType = \Drupal::entityTypeManager()->getDefinition($this->getFieldSetting('target_type'));
    }

    return $this->entityType;
  }

  /**
   * Returns the entity reference label setting.
   *
   * @return string
   *   The entity reference label setting.
   */
  protected function getEntityReferenceLabel() {
    if ($label = $this->getSetting('entity_reference_label')) {
      return $label;
    }

    return (string) $this->getTargetEntityType()->getLabel();
  }

  /**
   * Returns the quantity label setting.
   *
   * @return string
   *   The quantity label setting.
   */
  protected function getQuantityLabel() {
    if ($label = $this->getSetting('quantity_label')) {
      return $label;
    }

    return 'Quantity';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'entity_reference_label' => '',
      'quantity_label' => '',
      'quantity_prefix' => '',
      'quantity_suffix' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['entity_reference_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reference label'),
      '#default_value' => $this->getSetting('entity_reference_label'),
      '#description' => $this->t('The header label to use for the entity reference field.'),
    ];

    $elements['quantity_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quantity label'),
      '#default_value' => $this->getSetting('quantity_label'),
      '#description' => $this->t('The header label to use for the quantity field.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('Entity Reference label: @label', ['@label' => $this->getEntityReferenceLabel()]);
    $summary[] = $this->t('Quantity label: @label', ['@label' => $this->getQuantityLabel()]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Get Settings.
    $entity_reference_label = $this->getEntityReferenceLabel();
    $quantity_label = $this->getQuantityLabel();

    // Initialize values.
    $elements = [];
    $elements_entity_reference = parent::viewElements($items, $langcode);
    $rows = [];
    $values = $items->getValue();

    // Build rows.
    foreach ($elements_entity_reference as $delta => $element) {
      $rows[] = [
        [
          'data' => $element,
        ],
        [
          'data' => $values[$delta]['quantity'] ?: 0,
        ],
      ];
    }

    // Build table.
    $elements[0] = [
      '#type' => 'table',
      '#header' => [
        $this->t('@label', ['@label' => $entity_reference_label]),
        $this->t('@label', ['@label' => $quantity_label]),
      ],
      '#empty' => $this->t('None available.'),
      '#attributes' => [
        'class' => ['commerce-inventory-quantity-table'],
      ],
      '#rows' => $rows,
    ];

    return $elements;
  }

}
