<?php

/**
 * @file
 * Contains \Drupal\itchio_field\Plugin\Field\FieldWidget\ItchioIframeWidget.
 */

namespace Drupal\itchio_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'itchio_iframe' widget.
 *
 * @FieldWidget(
 *   id = "itchio_iframe",
 *   module = "itchio_field",
 *   label = @Translation("Itch.io iframe"),
 *   field_types = {
 *     "itchio_field_itchio"
 *   }
 * )
 */
class ItchioIframeWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $linkback = isset($items[$delta]->linkback) ? $items[$delta]->linkback : '';
    $borderwidth = isset($items[$delta]->borderwidth) ? $items[$delta]->borderwidth : '';
    $bg_color = isset($items[$delta]->bg_color) ? $items[$delta]->bg_color : '';
    $fg_color = isset($items[$delta]->fg_color) ? $items[$delta]->fg_color : '';
    $link_color = isset($items[$delta]->link_color) ? $items[$delta]->link_color : '';
    $border_color = isset($items[$delta]->border_color) ? $items[$delta]->border_color : '';
    $width = isset($items[$delta]->width) ? $items[$delta]->width : '';
    $height = isset($items[$delta]->height) ? $items[$delta]->height : '';

    $use_button = isset($items[$delta]->use_button) ? $items[$delta]->use_button : '';
    $button_text = isset($items[$delta]->button_text) ? $items[$delta]->button_text : '';
    $button_user = isset($items[$delta]->button_user) ? $items[$delta]->button_user : '';
    $button_project = isset($items[$delta]->button_project) ? $items[$delta]->button_project : '';

    $use_button_name = '';
    if (!empty($element['#field_parents'])) {
      $use_button_name = array_shift($element['#field_parents']);

      foreach ($element['#field_parents'] as $field_parent) {
        $use_button_name .= '[' . $field_parent . ']';
      }
    }
    $use_button_name .= '[' . $items->getName() . '][' . $delta . '][use_button]';
    $use_button_name = ':input[name="' . $use_button_name . '"]';

    $element['use_button'] = [
      '#type' => 'radios',
      '#title' => t('Itch game link type'),
      '#default_value' => $use_button,
      '#options' => [
        0 => $this->t('iframe'),
        1 => $this->t('JS API button'),
      ],
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $element['value'] = [
      '#type' => 'textfield',
      '#title' => t('Itchio Project Number'),
      '#description' => $this->t('Your game\'s project number on Itch.io. This is the last value of the url on your game\'s edit page - i.e. http://itch.io/game/edit/XXXXX'),
      '#default_value' => $value,
      '#states' => [
        'visible' => [
          [$use_button_name => ['value' => 0]]
        ],
      ],
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $element['linkback'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include link to itch.io page'),
      '#default_value' => $linkback,
      '#states' => [
        'visible' => [
          [$use_button_name => ['value' => 0]]
        ],
      ],
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $element['width'] = [
      '#type' => 'number',
      '#title' => $this->t('iframe Width'),
      '#default_value' => $width,
      '#states' => [
        'visible' => [
          [$use_button_name => ['value' => 0]]
        ],
      ],
      '#size' => 4,
      '#prefix' => '<p>',
    ];
    $element['height'] = [
      '#type' => 'number',
      '#title' => $this->t('iframe Height'),
      '#default_value' => $height,
      '#states' => [
        'visible' => [
          [$use_button_name => ['value' => 0]]
        ],
      ],
      '#size' => 4,
      '#suffix' => '</p>',
    ];
    $element['bg_color'] = [
      '#type' => 'textfield',
      '#title' => t('Background color'),
      '#default_value' => $bg_color,
      '#states' => [
        'visible' => [
          [$use_button_name => ['value' => 0]]
        ],
      ],
      '#size' => 6,
      '#prefix' => '<p>',
    ];
    $element['fg_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Foreground color'),
      '#default_value' => $fg_color,
      '#states' => [
        'visible' => [
          [$use_button_name => ['value' => 0]]
        ],
      ],
      '#size' => 6,
    ];
    $element['link_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link color'),
      '#default_value' => $link_color,
      '#states' => [
        'visible' => [
          [$use_button_name => ['value' => 0]]
        ],
      ],
      '#size' => 6,
      '#suffix' => '</p>',
    ];
    $element['borderwidth'] = [
      '#type' => 'number',
      '#title' => $this->t('Border width'),
      '#default_value' => $borderwidth,
      '#states' => [
        'visible' => [
          [$use_button_name => ['value' => 0]]
        ],
      ],
      '#size' => 1,
      '#prefix' => '<p>',
    ];
    $element['border_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border color'),
      '#default_value' => $border_color,
      '#states' => [
        'visible' => [
          [$use_button_name => ['value' => 0]]
        ],
      ],
      '#size' => 6,
      '#suffix' => '</p>',
    ];

    $element['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#default_value' => $button_text,
      '#states' => [
        'visible' => [
          [$use_button_name => ['value' => 1]]
        ],
      ],
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $element['button_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Itch.io username'),
      '#default_value' => $button_user,
      '#states' => [
        'visible' => [
          [$use_button_name => ['value' => 1]]
        ],
      ],
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $element['button_project'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Itch.io project name'),
      '#default_value' => $button_project,
      '#states' => [
        'visible' => [
          [$use_button_name => ['value' => 1]]
        ],
      ],
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];



    // If cardinality is 1, ensure a label is output for the field by wrapping
    // it in a details element.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      $element += [
        '#type' => 'details',
        '#attributes' => ['class' => ['container-inline']],
        '#open' => TRUE,
      ];
    }

    return $element;
  }

}
