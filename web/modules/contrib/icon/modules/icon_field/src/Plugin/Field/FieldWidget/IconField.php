<?php

namespace Drupal\icon_field\Plugin\Field\FieldWidget;

/**
 * @file
 * Contains \Drupal\icon_field\Plugin\Field\FieldWidget\IconField.
 */
use Drupal;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'icon_default_widget' widget.
 *
 * @FieldWidget(
 *   id = "icon_default_widget",
 *   label = @Translation("Icon"),
 *   field_types = {
 *     "icon"
 *   }
 * )
 */
class IconField extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $element += array(
      '#type' => 'textfield',
      '#default_value' => $value,
      '#element_validate' => array(
        array($this, 'validate'),
      ),
    );
    return array('icon' => $element);
  }

  /**
   * Validate the icon field.
   */
  public function validate($element, FormStateInterface $form_state) {
    $value = $element['#icon'];
    if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }
  }

}
