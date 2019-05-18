<?php

namespace Drupal\flexfield\Plugin\FlexFieldType;

use Drupal\flexfield\Plugin\FlexFieldTypeBase;
use Drupal\flexfield\Plugin\Field\FieldType\FlexItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'text' flexfield type.
 *
 * Simple textfield flexfield widget. Value renders as it is entered by the
 * user.
 *
 * @FlexFieldType(
 *   id = "text",
 *   label = @Translation("Text"),
 *   description = @Translation("")
 * )
 */
class Text extends FlexFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);
    // Add our widget type and additional properties and return.
    return [
      '#type' => 'textfield',
      '#maxlength' => $this->max_length,
      '#size' => NULL,
    ] + $element;
  }

}
