<?php

namespace Drupal\checklist_entity_reference\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Form\OptGroup;

/**
 * Plugin implementation of the 'options_buttons' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_checklist_options",
 *   label = @Translation("Check boxes/radio buttons"),
 *   field_types = {
 *     "entity_reference_checklist",
 *   },
 *   multiple_values = TRUE
 * )
 */
class EntityReferenceChecklistOptions extends OptionsButtonsWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $options = $this->getOptions($items->getEntity());

    // We need to check against a flat list of options.
    $flat_options = OptGroup::flattenOptions($options);

    $selected = [];
    foreach ($items as $item) {
      $value = $item->{$this->column};
      // Keep the value if it actually is in the list of options (needs to be
      // checked against the flat list).
      if (isset($flat_options[$value])) {
        $selected[] = $value;
        $checkedDate = \Drupal::service('date.formatter')->format($item->checked_date, 'date_time_format');
        $checkedUser = \Drupal::entityManager()->getStorage('user')->load($item->checked_by);
        $element[$value] = [
          '#disabled' => TRUE,
          '#description' => t("Completed @date by @user", [
            '@date' => $checkedDate,
            '@user' => $checkedUser->getDisplayName(),
          ]),
        ];
      }
    }

    // If required and there is one single option, preselect it.
    if ($this->required && count($options) == 1) {
      reset($options);
      $selected = [key($options)];
    }

    if ($this->multiple) {
      $element += [
        '#type' => 'checkboxes',
        '#default_value' => $selected,
        '#options' => $options,
      ];
    }
    else {
      $element += [
        '#type' => 'radios',
        // Radio buttons need a scalar value. Take the first default value, or
        // default to NULL so that the form element is properly recognized as
        // not having a default value.
        '#default_value' => $selected ? reset($selected) : NULL,
        '#options' => $options,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $entity = $form_state->getFormObject()->getEntity();
    $fieldName = $this->fieldDefinition->get('field_name');
    $preChecked = [];
    if (!$entity->get($fieldName)) {
      return;
    }
    if (!$entity->get($fieldName)->isEmpty()) {
      foreach ($entity->get($fieldName) as $item) {
        $value = $item->getValue();
        $preChecked[$value['target_id']] = $value;
      }
    }
    foreach ($values as $id => $value) {
      if (isset($preChecked[$value['target_id']])) {
        unset($values[$id]);
      }
    }
    foreach ($preChecked as $preCheckedItem) {
      $values[] = $preCheckedItem;
    }
    return $values;
  }

}
