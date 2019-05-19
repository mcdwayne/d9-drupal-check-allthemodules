<?php

namespace Drupal\stock_photo_search\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'stock_photo_search_textfield' widget.
 *
 * @FieldWidget(
 *   id = "stock_photo_search_textfield",
 *   label = @Translation("Stock Photo Textfield"),
 *   field_types = {
 *     "stock_photo_search"
 *   }
 * )
 */
class StockPhotoTextfield extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => 60,
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#attributes' => [
        'readonly' => 'readonly',
      ],
      '#allowed_providers' => $this->getFieldSetting('allowed_providers'),
    ];

    if (!$this->getFieldSetting('allowed_providers')) {
      $element['markup'] = [
        '#type' => 'markup',
        '#markup' => $this->t('You need to select one provider'),
      ];
    }
    else {
      $element['btn_open_modal'] = [
        '#type' => 'link',
        '#title' => $this->t('Search Image...'),
        '#url' => Url::fromRoute('stock_photo_search.open_modal_form', ['provider' => $this->getFieldSetting('allowed_providers')]),
        '#allowed_providers' => $this->getFieldSetting('allowed_providers'),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'button',
            'btn_open_modal',
          ],
        ],
      ];

      $element['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $element['#attached']['library'][] = 'stock_photo_search/my_scripts';
    }

    return $element;
  }

}
