<?php

/**
 * @file
 * Contains \Drupal\ooyala\Plugin\Field\FieldWidget\OoyalaVideoWidget.
 */

namespace Drupal\ooyala\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ooyala\OoyalaManager;

/**
 * Plugin implementation of the 'ooyala_video_select' widget.
 *
 * @FieldWidget(
 *   id = "ooyala_video_select",
 *   module = "ooyala",
 *   label = @Translation("Video selection widget"),
 *   field_types = {
 *     "ooyala_video"
 *   }
 * )
 */
class OoyalaVideoWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    $item = $items->get($delta);
    $rec = $item ? json_decode($item->item) : false;

    $element = [

      'item_select' => [
        '#prefix' => '<div class="ooyala-pick-video">',
        '#type' => 'textfield',
        '#placeholder' => $this->t('Search for a video...'),
        '#size' => 1,
        '#autocomplete_route_name' => 'ooyala.search_videos',
        '#states' => [
          'visible' => [
            ':input[name="' . $item->getParent()->getName() . '[' . $delta . '][item]"]' => ['value' => ''],
          ],
        ],
      ],

      'item_upload' => [
        '#type' => 'button',
        '#value' => $this->t('Upload Video...'),
        '#states' => [
          'visible' => [
            ':input[name="' . $item->getParent()->getName() . '[' . $delta . '][item]"]' => ['value' => ''],
          ],
        ],
        '#suffix' => '</div>',
      ],

      'item' => [
        '#type' => 'hidden',
        '#default_value' => $item->item,
      ],

      'details' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['ooyala-video-details', 'group'],
        ],
        '#states' => [
          'visible' => [
            ':input[name="' . $item->getParent()->getName() . '[' . $delta . '][item]"]' => ['!value' => ''],
          ],
        ],

        'item_cancel' => [
          '#type' => 'button',
          '#value' => 'X',
          '#attributes' => [
            'class' => ['ooyala-item-cancel'],
          ],
        ],

        'item_name' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['ooyala-item-name'],
          ],
          '#value' => $rec ? $rec->name : '',
        ],

        'item_code' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['ooyala-item-code'],
          ],
          '#value' => $rec ? $rec->embed_code : '',
        ],

        'item_description' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['ooyala-item-description'],
          ],
          '#value' => $rec ? $rec->description : '',
        ],

        'item_image' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['ooyala-selected-image'],
            'style' => $rec ? 'background-image: url(' . $rec->preview_image_url . ')' : '',
          ],
        ],
      ],

      'initial_time' => [
        '#prefix' => '<div class="ooyala-video-settings">',
        '#title' => $this->t("Initial Time"),
        '#type' => 'number',
        '#min' => 0,
        '#size' => '3',
        '#states' => [
          'visible' => [
            ':input[name="' . $item->getParent()->getName() . '[' . $delta . '][item]"]' => ['!value' => ''],
          ],
        ],
        '#default_value' => isset($item->initial_time) ? $item->initial_time : 0,
      ],

      'initial_volume' => [
        '#title' => $this->t("Initial Volume"),
        '#type' => 'number',
        '#min' => 0,
        '#max' => 100,
        '#size' => '3',
        '#states' => [
          'visible' => [
            ':input[name="' . $item->getParent()->getName() . '[' . $delta . '][item]"]' => ['!value' => ''],
          ],
        ],
        '#default_value' => isset($item->initial_volume) ? $item->initial_volume : 100,

      ],
      'autoplay' => [
        '#title' => $this->t("Autoplay?"),
        '#type' => 'checkbox',
        '#states' => [
          'visible' => [
            ':input[name="' . $item->getParent()->getName() . '[' . $delta . '][item]"]' => ['!value' => ''],
          ],
        ],
        '#default_value' => isset($item->autoplay) ? $item->autoplay : 0,
      ],
      'loop' => [
        '#title' => $this->t("Loop?"),
        '#type' => 'checkbox',
        '#states' => [
          'visible' => [
            ':input[name="' . $item->getParent()->getName() . '[' . $delta . '][item]"]' => ['!value' => ''],
          ],
        ],
        '#default_value' => isset($item->loop) ? $item->loop : 0,
      ],
      'additional_params' => [
        '#title' => $this->t("Additional Params (JSON)"),
        '#type' => 'ooyala_json_textarea',
        '#placeholder' => $this->t('Key/value pairs in JSON or JavaScript object literal notation'),
        '#states' => [
          'visible' => [
            ':input[name="' . $item->getParent()->getName() . '[' . $delta . '][item]"]' => ['!value' => ''],
          ],
        ],
        '#default_value' => $item ? $item->additional_params : '',
        '#suffix' => '</div>',
      ],
    ];

    $element['#attached']['library'][] = 'ooyala/ooyala_widget';
    $element['#attached']['drupalSettings']['ooyala'] = [
      'chunkSize' => OoyalaManager::CHUNK_SIZE
    ];
    $element['#validate'][] = [$this, 'validateForm'];

    return $element;
  }

}
