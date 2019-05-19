<?php

namespace Drupal\simple_iframe\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'simple_iframe_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "simple_iframe_widget_type",
 *   label = @Translation("Simple iframe widget type"),
 *   field_types = {
 *     "simple_iframe_field_type"
 *   }
 * )
 */
class SimpleIframeWidgetType extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 100,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];
    $field_settings = $this->getFieldSettings();
    $settings = $this->getSettings() + $field_settings;

    $element['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Iframe URL'),
      '#description' => $this->t('Set the source of the iframe'),
      '#placeholder' => '//',
      '#default_value' => isset($items[$delta]->url) ? $items[$delta]->url : NULL,
      '#size' => $this->getSetting('size'),
      '#maxlength' => 2000,
      '#weight' => 1,
      '#required' => $element['#required'],
    ];
    $width = (isset($item->width) && !empty($item->width)) ? $item->width
        : (isset($settings['width']) ? $settings['width'] : NULL);
    $element['width'] = [
      '#title' => $this->t('Width'),
      '#description' => $this->t('Set a number or %'),
      '#type' => 'textfield',
      '#default_value' => $width,
      '#required' => $element['#required'],
      '#maxlength' => 8,
      '#size' => 6,
      '#weight' => 3,
    ];

    $height = (isset($item->height) && !empty($item->height)) ? $item->height
        : (isset($settings['height']) ? $settings['height'] : NULL);
    $element['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#description' => $this->t('Set a number'),
      '#default_value' => $height,
      '#maxlength' => 8,
      '#size' => 6,
      '#weight' => 4,
      '#required' => $element['#required'],
    ];

    return $element;
  }

}
