<?php

namespace Drupal\header_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'header_field_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "header_field_widget_type",
 *   label = @Translation("Header field widget type"),
 *   field_types = {
 *     "header_field_type"
 *   }
 * )
 */
class HeaderFieldWidgetType extends WidgetBase {
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += ['#type' => 'fieldset',];
    $element['value'] = [
        '#type' => 'textfield',
        '#title' => t('Header Title'),
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
        '#size' => 150,
    ];
    $element['weight'] = [
        '#default_value' => isset($items[$delta]->weight) ? $items[$delta]->weight : NULL,
        '#type' => 'select',
        '#title' => $this->t('Select font weight'),
        '#options' => [
            'light' => $this->t('Light'),
            'medium' => $this->t('Medium'),
            'bold' => $this->t('Bold'),
     ],
 ];
    $element['alignment'] = [
        '#default_value' => isset($items[$delta]->alignment) ? $items[$delta]->alignment : NULL,
        '#type' => 'select',
        '#title' => $this->t('Select alignment'),
        '#options' => [
            'left' => $this->t('Left'),
            'center' => $this->t('Center'),
            'right' => $this->t('Right'),
     ],
 ];
    $element['h_tag'] = [
        '#default_value' => isset($items[$delta]->h_tag) ? $items[$delta]->h_tag : NULL,
        '#type' => 'select',
        '#title' => $this->t('Select H Tag'),
        '#options' => [
            'h1' => $this->t('H1'),
            'h2' => $this->t('H2'),
            'h3' => $this->t('H3'),
            'h4' => $this->t('H4'),
            'h5' => $this->t('H5'),
            'h6' => $this->t('H6'),
     ],
 ];
    return $element;
  }

}
