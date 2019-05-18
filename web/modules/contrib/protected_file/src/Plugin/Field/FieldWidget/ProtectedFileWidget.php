<?php

namespace Drupal\protected_file\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'pbf_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "protected_file_widget",
 *   label = @Translation("Protected File widget"),
 *   field_types = {
 *     "protected_file"
 *   }
 * )
 */
class ProtectedFileWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function value($element, $input = FALSE, FormStateInterface $form_state) {
    $return = parent::value($element, $input, $form_state);

    // Ensure that all the required properties are returned even if empty.
    $return += array(
      'protected_file' => 0,
    );

    return $return;
  }

  /**
   * Overrides \Drupal\file\Field\FieldWidget\FileWidget::formMultipleElements().
   *
   * Adding the protected option into the table.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);

    // Determine the number of widgets to display.
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $max = count($items);
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    if ($is_multiple) {
      $elements['#theme'] = 'protected_file_widget_multiple';
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);

    $item = $element['#value'];
    $item['fids'] = $element['fids']['#value'];

    if ($item['fids']) {
      $element['protected_file'] = array(
        '#type' => 'checkbox',
        '#title' => t('Protected'),
        '#return_value' => (int) 1,
        '#empty' => 0,
        '#value' => ($item['protected_file']) ? 1 : 0,
      );
    }

    return $element;
  }

}
