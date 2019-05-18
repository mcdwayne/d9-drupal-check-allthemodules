<?php

namespace Drupal\text_with_title\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'TextWithTitleWidget' widget.
 *
 * @FieldWidget(
 *   id = "text_with_title_widget",
 *   module = "text_with_title",
 *   label = @Translation("Text with title"),
 *   field_types = {
 *     "text_with_title_field"
 *   }
 * )
 */
class TextWithTitleWidget extends WidgetBase {

  /**
   * Define the form for the field type.
   *
   * Inside this method we can define the form used to edit the field type.
   *
   * Here there is a list of allowed element types: https://goo.gl/XVd4tA
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $formState
    ) {

    // Title.
    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => isset($items[$delta]->title) ?
      $items[$delta]->title : NULL,
      '#empty_value' => '',
      '#placeholder' => $this->t('Title'),
    ];
    // Text.
    $element['text'] = [
      '#type' => 'text_format',
      '#format' => isset($items[$delta]->text['format']) ?
      $items[$delta]->text['format'] : 'basic_html',
      '#title' => $this->t('Text'),
      '#default_value' => isset($items[$delta]->text['value']) ?
      $items[$delta]->text['value'] : '',
      '#empty_value' => '',
      '#placeholder' => $this->t('Text'),
    ];
    return $element;
  }

}
