<?php

namespace Drupal\landingpage\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'paragraph_display_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "paragraph_display_field_widget",
 *   label = @Translation("Paragraph display field widget"),
 *   field_types = {
 *     "paragraph_display_field_type"
 *   }
 * )
 */
class ParagraphDisplayFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';

    $view_modes = \Drupal::entityManager()->getViewModeOptionsByBundle($items->getFieldDefinition()->getTargetEntityTypeId(), $items->getFieldDefinition()->getTargetBundle());

    $element = array(
      '#type' => 'select',
      '#title' => $this->t('Paragraph View Mode'),
      '#options' => $view_modes,
      '#default_value' => $value,
      '#multiple' => FALSE,
    );

    return array('value' => $element);
  }

}
