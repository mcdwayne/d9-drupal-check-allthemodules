<?php

namespace Drupal\password_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * A widget Password.
 *
 * @FieldWidget(
 *   id = "WidgetPassword",
 *   label = @Translation("Password Widget"),
 *   field_types = {
 *     "Password",
 *     "string"
 *   }
 * )
 */
class PasswordWidget extends WidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        // Create a default setting 'size', and
        // assign a default value of 60.
      'size' => 60,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['value'] = $element + [
      '#type' => 'password',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => $this->getSetting('size'),
      '#delta' => $delta,
    ];

    return $element;
  }

}
