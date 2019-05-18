<?php

namespace Drupal\field_group_as_class\Plugin\field_group\FieldGroupFormatter;

use Drupal\Core\Render\Element;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Plugin implementation of the 'asclass' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "asclass",
 *   label = @Translation("As Class"),
 *   description = @Translation("Renders a field group as a class."),
 *   supported_contexts = {
 *     "view",
 *   },
 *   supported_field_types = {
 *    "string",
 *    "list_string",
 *   }
 * )
 */
class AsClass extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = [
      'classes' => '',
      'field_class' => '',
    ] + parent::defaultSettings($context);

    if ($context == 'form') {
      $defaults['required_fields'] = 1;
    }

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $form = parent::settingsForm();

    $options = [
      'entity' => $this->t('Full @entity_type page', ['@entity_type' => $this->group->entity_type]),
    ];

    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($this->group->entity_type, $this->group->bundle);
    foreach ($fields as $field) {
      if (in_array($field->getType(), $this->pluginDefinition['supported_field_types']) && $field->getFieldStorageDefinition()->isBaseField() == FALSE) {
        $options[$field->getName()] = $field->getLabel();
      }
    }

    $form['field_class'] = [
      '#title' => $this->t('Select the Field Class'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('field_class'),
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    // Get the entity key from the entity type.
    $entity_key = '#' . $this->group->entity_type;

    if (!isset($rendering_object[$entity_key])) {

      // Some entity types store the key in an arbitrary name.
      // Check for the ones that we know of.
      switch ($this->group->entity_type) {
        case 'paragraph':
          $entity_key = '#paragraph';
          break;

        case 'taxonomy_term':
          $entity_key = '#term';
          break;

        case 'user':
          $entity_key = '#account';
          break;

        // Otherwise just search for #entity.
        default:
          $entity_key = '#entity';
      }
    }

    if (isset($rendering_object[$entity_key]) && is_object($rendering_object[$entity_key])) {
      $entity = $rendering_object[$entity_key];
    }
    else {
      // We can't find the entity.
      return;
    }

    if (
      !empty($entity) &&
      in_array($entity->getEntityTypeId(), [
        'node',
        'paragraph',
        'taxonomy_term',
        'block_content',
      ])) {
      $element += [
        '#type' => 'field_group_as_class',
        '#field_class' => $this->getFieldClassValue($entity),
        '#options' => [
          'attributes' => [
            'class' => $this->getClasses(),
          ],
        ],
      ];

      if (!empty($this->getSetting('id'))) {
        $element['#options']['attributes']['id'] = $this->getSetting('id');
      }

      // Copy each child element into the link title.
      // Create a reference in case the content has not yet been generated.
      foreach (Element::children($element) as $group_child) {
        $element['#title'][$group_child] = &$element[$group_child];
      }
    }
  }

  /**
   * Return FieldClass value.
   */
  protected function getFieldClassValue($entity) {

    $field_name = $this->getSetting('field_class');
    $field_class = $entity->get($field_name)->getValue();

    if (!empty($field_class[0]['value'])) {
      return $field_class[0]['value'];
    }
  }

}
