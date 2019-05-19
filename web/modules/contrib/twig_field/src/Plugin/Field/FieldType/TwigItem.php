<?php

namespace Drupal\twig_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the Twig field type.
 *
 * @FieldType(
 *   id = "twig",
 *   label = @Translation("Twig template"),
 *   category = @Translation("General"),
 *   default_widget = "twig",
 *   default_formatter = "twig"
 * )
 */
class TwigItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return ['display_mode' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $definition = $this->getFieldDefinition();
    $entity_type = $definition->getTargetEntityTypeId();
    $bundle = $definition->getTargetBundle();

    $display_modes = \Drupal::service('entity_display.repository')
      ->getViewModeOptionsByBundle($entity_type, $bundle);

    $options = ['' => $this->t('- None -')];
    foreach ($display_modes as $key => $label) {
      $id = $entity_type . '.' . $bundle . '.' . $key;
      $options[$id] = $label;
    }

    $element['display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Display mode to fetch context from'),
      '#description' => $this->t('The template will receive an additional Twig context from parent entity rendered with the specified display mode.'),
      '#default_value' => $this->getSetting('display_mode'),
      '#options' => $options,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return [
      'value' => DataDefinition::create('string')->setLabel(t('Template')),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'big',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->value === NULL || $this->value === '';
  }

}
