<?php

namespace Drupal\taggd\Plugin\Field\FieldWidget;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;

/**
 * Plugin implementation of the 'prices' widget.
 *
 * @FieldWidget(
 *   id = "taggd_image",
 *   label = @Translation("Taggd Image"),
 *   field_types = {
 *     "taggd_image"
 *   }
 * )
 */
class TaggdImageWidget extends ImageWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
    $field_item = $items[$delta];

    $default_value = !empty($field_item->taggd_image_data) ? $field_item->taggd_image_data : [];
    if (is_array($default_value)) {
      // Encode default value as JSON string.
      $default_value = Json::encode($default_value);
    }

    $element['taggd_image_data'] = [
      '#type' => 'hidden',
      '#title' => 'Tag data',
      '#value' => $default_value,
      '#attributes' => [
        'class' => ['taggd-image-data'],
      ],
    ];

    if (!empty($field_item->fids) && !empty($field_item->taggd_image_data)) {
      /** @var array $fids */
      // Extract fid.
      $fids = $field_item->fids;
      $fid = reset($fids);
      // Build js settings.
      $js_settings = is_array($field_item->taggd_image_data) ? $field_item->taggd_image_data : Json::decode($field_item->taggd_image_data);
      // Attach settings.
      $element['taggd_image_data']['#attached']['drupalSettings']['taggd_widget'][$fid] = $js_settings;
    }

    // Attach the taggd library.
    $element['taggd_image_data']['#attached']['library'][] = 'taggd/taggd.widget';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);

    foreach ($values as $delta => &$item_values) {
      if (!empty($item_values['taggd_image_data'])) {
        // Format as array because our field defines a "MapDataDefinition"
        // property which expects an array.
        $item_values['taggd_image_data'] = Json::decode($item_values['taggd_image_data']);
      }
      else {
        unset($item_values['taggd_image_data']);
      }
    }

    return $values;
  }

}
