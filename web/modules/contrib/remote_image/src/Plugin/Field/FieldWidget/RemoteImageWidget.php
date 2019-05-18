<?php

/**
 * @file
 * Contains Drupal\remote_image\Plugin\Field\FieldWidget\RemoteImageWidget.
 */

namespace Drupal\remote_image\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Plugin implementation of the 'remote_image' widget.
 *
 * @FieldWidget(
 *   id = "remote_image",
 *   label = @Translation("Remote Image"),
 *   field_types = {
 *     "remote_image"
 *   }
 * )
 */
class RemoteImageWidget extends LinkWidget {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'placeholder_url' => '',
      'placeholder_title' => '',
      'placeholder_alt' => '',
      'placeholder_width' => '',
      'placeholder_height' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Get the field settings.
    $field_settings = $this->fieldDefinition->getSettings();
    $elements =  parent::settingsForm($form, $form_state) + [
      'placeholder_alt' => !$field_settings['alt_field'] ? [] : [
        '#type' => 'textfield',
        '#title' => $this->t('Placeholder for alt text'),
        '#default_value' => $this->getSetting('placeholder_alt'),
        '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
      ],
    ];

    $elements['placeholder_title'] = !$field_settings['title_field'] ? [] : [
      '#title' => $this->t('Placeholder for image title'),
      '#states' => [],
    ] + $elements['placeholder_title'];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['uri']['#weight'] = 5;
    //@todo: Use the image style title rather than the link style title.
    /** @var \Drupal\link\LinkItemInterface $item */
    $item = $items[$delta];

    return [
      'alt' => [
        '#type' => 'textfield',
        '#title' => $this->t('Alternative text'),
        '#description' => $this->t('This text will be used by screen readers, search engines, and when the image cannot be loaded.'),
        '#default_value' => $item->alt,
        '#placeholder' => $this->getSetting('placeholder_alt'),
        '#weight' => 10,
        '#maxlength' => 512,
      ],
      'title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#description' => t('The title attribute is used as a tooltip when the mouse hovers over the image.'),
        '#default_value' => $item->title,
        '#placeholder' => $this->getSetting('placeholder_title'),
        '#weight' => 15,
        '#maxlength' => 1024,
      ],
      'width' => [
        '#type' => 'number',
        '#title' => $this->t('Width'),
        '#description' => t('The width of the image'),
        '#weight' => 20,
        // @FIXME Hack to pass entity validation.
        '#default_value' => $item->width ?: 0,
      ],
      'height' => [
        '#type' => 'number',
        '#title' => $this->t('Height'),
        '#description' => t('The height of the image.'),
        '#weight' => 25,
        // @FIXME Hack to pass entity validation.
        '#default_value' => $item->height ?: 0,
      ],
    ] + $element;
  }
}
