<?php

namespace Drupal\group_content_field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityStorageBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\rel_content\RelatedContentInterface;

/**
 * Plugin implementation of the 'plugin_reference_select' widget.
 *
 * @FieldWidget(
 *   id = "group_select",
 *   label = @Translation("Group select"),
 *   field_types = {
 *     "group_content_item"
 *   }
 * )
 */
class GroupSelectWidget extends WidgetBase {

  /**
   * Get all available options.
   */
  protected function getGroups($group_type) {
    $options = [];

    foreach(\Drupal::entityTypeManager()->getStorage('group')->loadByProperties(['type' => $group_type]) as $key => $group) {
      if ($group->access('view')) {
        $options[$key] = $group->label();
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $values = $items[$delta]->getValue();

    $options = $this->getGroups($this->fieldDefinition->getFieldStorageDefinition()->getSetting('group_type'));
    asort($options);

    $element['entity_gids'] = $element + [
        '#type' => 'select',
        '#default_value' => isset($values['entity_gids']) ? $values['entity_gids'] : NULL,
        '#options' => $options,
        '#multiple' => TRUE,
        '#chosen' => TRUE,
      ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    // Extract the values from $form_state->getValues().
    $path = array_merge($form['#parents'], array($field_name));
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state->getValues(), $path, $key_exists);

    if ($key_exists) {
      foreach ($values as $delta => &$value) {
        $form_state->getValues()[$field_name][$delta]['from_widget'] = TRUE;
      }
    }

    parent::extractFormValues($items, $form, $form_state);
  }

}
