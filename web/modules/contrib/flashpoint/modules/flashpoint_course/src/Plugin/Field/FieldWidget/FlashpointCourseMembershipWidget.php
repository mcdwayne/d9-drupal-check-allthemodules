<?php

namespace Drupal\flashpoint_course\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\flashpoint_course\FlashpointCourseUtilities;

/**
 * Plugin implementation of the 'flashpoint_course_membership_widget' widget.
 *
 * @FieldWidget(
 *   id = "flashpoint_course_membership_widget",
 *   label = @Translation("Flashpoint course membership widget"),
 *   field_types = {
 *     "flashpoint_course_membership_type"
 *   },
 *   multiple_values = TRUE
 * )
 */
class FlashpointCourseMembershipWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  protected function sanitizeLabel(&$label) {
    // Select form inputs allow unencoded HTML entities, but no HTML tags.
    $label = Html::decodeEntities(strip_tags($label));
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    if ($this->multiple) {
      // Multiple select: add a 'none' option for non-required fields.
      if (!$this->required) {
        return t('- None -');
      }
    }
    else {
      // Single select: add a 'none' option for non-required fields,
      // and a 'select a value' option for required fields that do not come
      // with a value selected.
      if (!$this->required) {
        return t('- None -');
      }
      if (!$this->has_value) {
        return t('- Select a value -');
      }
    }
  }

  protected function getOptions($entity) {
    return FlashpointCourseUtilities::getOptions('course');
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element += [
      '#type' => 'select',
      '#options' => $this->getOptions($items->getEntity()),
      '#empty_option' => $this->getEmptyLabel(),
      '#default_value' => $this->getSelectedOptions($items),
      // Do not display a 'multiple' select box if there is only one option.
      '#multiple' => $this->multiple && count($this->getOptions($items->getEntity())) > 1,
    ];

    return $element;
  }
}
