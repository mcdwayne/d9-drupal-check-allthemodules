<?php

namespace Drupal\taggd\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'image' formatter.
 *
 * @FieldFormatter(
 *   id = "taggd_image",
 *   label = @Translation("Taggd Image"),
 *   field_types = {
 *     "taggd_image"
 *   }
 * )
 */
class TaggdImageFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'taggd_tag_show_event' => 'mouseenter',
      'taggd_tag_hide_event' => 'mouseleave',
      'taggd_tag_hide_delay' => 500,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    // Don't allow image to link to something.
    unset($element['image_link']);

    // Add taggd settings.
    $element['taggd_tag_show_event'] = [
      '#type' => 'select',
      '#title' => $this->t('The event to show the tag'),
      '#options' => [
        'mouseenter' => 'Mouse enter',
        'click' => 'Click',
      ],
      '#default_value' => $this->getSetting('taggd_tag_show_event'),
      '#required' => TRUE,
    ];

    $element['taggd_tag_hide_event'] = [
      '#type' => 'select',
      '#title' => $this->t('The event to hide the tag'),
      '#options' => [
        'mouseleave' => 'Mouse leave',
        'click' => 'Click',
      ],
      '#default_value' => $this->getSetting('taggd_tag_hide_event'),
      '#required' => TRUE,
    ];

    $element['taggd_tag_hide_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('The duration before the tag popup is actually hidden (ms)'),
      '#step' => 1,
      '#default_value' => $this->getSetting('taggd_tag_hide_delay'),
      '#description' => $this->t('If there is spacing between the tag button and popup, and you use mouseover/mouseout to toggle visiblity, you probably want to keep this.'),
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Let parent render elements.
    $elements = parent::viewElements($items, $langcode);
    $files = $this->getEntitiesToView($items, $langcode);
    $js_settings = [];

    foreach ($elements as $key => &$element) {
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      $item = $items[$key];
      /** @var \Drupal\file\FileInterface $file */
      $file = $files[$key];
      // Add some attributes the the image tag.
      $element['#item_attributes']['data-taggd'] = $file->id();
      $element['#item_attributes']['class'][] = 'taggd-image';
      if (!empty($item->taggd_image_data)) {
        // Extends js settings.
        $js_settings[$file->id()] = $item->taggd_image_data;
      }
      // Add a container.
      // We need this to set the width and the height of
      // The parent of the image... Which will be this container.
      $element['#prefix'] = '<div class="tagged-container">';
      $element['#suffix'] = '</div>';
    }

    // Attach the taggd library.
    $elements['#attached']['library'][] = 'taggd/taggd.formatter';
    // Attach settings.
    $elements['#attached']['drupalSettings']['taggd_formatter'] = $js_settings;
    $elements['#attached']['drupalSettings']['taggd_formatter']['options'] = [
      'show' => $this->getSetting('taggd_tag_show_event'),
      'hide' => $this->getSetting('taggd_tag_hide_event'),
      'hideDelay' => $this->getSetting('taggd_tag_hide_delay'),
    ];

    return $elements;

  }

}
