<?php

namespace Drupal\commerce_pado\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_product\Plugin\Field\FieldFormatter\AddToCartFormatter;

/**
 * Plugin implementation of the 'commerce_pado_add_to_cart' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_pado_add_to_cart",
 *   label = @Translation("Add to cart form with add-ons"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class PadoAddToCartFormatter extends AddToCartFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'add_on_field' => '',
      'multiple' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Multiple.'),
      '#description' => $this->t('Customer should be able to select multiple add-ons.'),
      '#default_value' => $this->getSetting('multiple'),
    ];

    $form['add_on_field'] = [
      '#type' => 'select',
      '#title' => $this->t('The product entity reference field to select add-ons from.'),
      '#description' => $this->t('All the variations belonging to the selected products in the field will be offered as add-ons.'),
      '#default_value' => $this->getSetting('add_on_field'),
      '#options' => $this->getReferenceFieldOptions($this->fieldDefinition),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $add_on_field = $this->getSetting('add_on_field');
    if (!empty($add_on_field)) {
      $summary[] = $this->t('The selected product add-on field is @field.', ['@field' => $this->getSetting('add_on_field')]);
    }
    else {
      $summary[] = $this->t('Please select a product add-on field.');
    }

    if ($this->getSetting('multiple')) {
      $summary[] = $this->t('The customer can select multiple add-ons.');
    }
    else {
      $summary[] = $this->t('The customer can select only one add-on.');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $elements[0]['add_to_cart_form'] = [
      '#lazy_builder' => [
        'commerce_pado.lazy_builders:addToCartWithAddOnsForm', [
          $items->getEntity()->id(),
          $this->viewMode,
          $this->getSetting('combine'),
          $this->getSetting('add_on_field'),
          $this->getSetting('multiple'),
        ],
      ],
      '#create_placeholder' => TRUE,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $return = parent::isApplicable($field_definition);
    $options = [];
    if ($return) {
      $options = self::getReferenceFieldOptions($field_definition);
    }
    return $return && !empty($options);
  }

  /**
   * Get candidates for product add-on fields.
   *
   * @param FieldDefinitionInterface $field_definition
   *   The field definition of the variations field we want to display
   *   add-ons for.
   *
   * @return array
   *   All the product entity reference fields on the same product keyed by
   *   their field machine names.
   */
  public static function getReferenceFieldOptions(FieldDefinitionInterface $field_definition) {
    $options = [];
    $entity_type = $field_definition->getTargetEntityTypeId();
    $bundle = $field_definition->getTargetBundle();
    if (empty($bundle)) {
      return $options;
    }
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
    /** @var \Drupal\field\Entity\FieldConfig $field */
    foreach ($fields as $field) {
      if ($field->getType() === 'entity_reference') {
        $field_storage_config = \Drupal\field\Entity\FieldStorageConfig::loadByName($entity_type, $field->getName());
        if ($field_storage_config && $field_storage_config->getSetting('target_type') == 'commerce_product') {
          $options[$field->getName()] = $field->getLabel() . ' (' . $field->getName() . ')';
        }
      }
    }
    return $options;
  }

}
