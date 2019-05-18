<?php

namespace Drupal\baidumap_fieldtype\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal;

/**
 * Plugin implementation of the 'BaidumapFieldtypeDefaultFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "BaidumapFieldtypeDefaultFormatter",
 *   label = @Translation("Baidu Map"),
 *   field_types = {
 *     "BaidumapFieldtype"
 *   }
 * )
 */
class BaidumapFieldtypeDefaultFormatter extends FormatterBase {

  /**
   * Define how the field type is showed.
   * 
   * Inside this method we can customize how the field is displayed inside 
   * pages.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];
    //var_dump($fieldSettings);exit;
    $width = $this->getSetting('width');
    $height = $this->getSetting('height');
    $title = $this->getSetting('title');

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'baidumap_fieldtype_formatter', 
        '#attached' => [
          'library' => [
            'baidumap_fieldtype/baidumap',
            'baidumap_fieldtype/baidumap.widget_fomatter',
            'baidumap_fieldtype/baidumap.api',
            'baidumap_fieldtype/baidumap.SearchInfoWindow'
          ],
          'drupalSettings' => [
            'baidumap' =>[
               'location' => $item->location,
               'address' => $item->address,
               'phone' => $item->phone,
               'profile' => $item->profile,
               'width' => $width,
               'height' => $height,
               'title' => $title
            ]
          ]
        ]
      ];
    }
    return $elements;
  }
  
  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = array();

    $element['width'] = [
      '#type' => 'number',
      '#title' => t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#placeholder' => t('Width'),
    ];

    $element['height'] = [
      '#type' => 'number',
      '#title' => t('Height'),
      '#default_value' => $this->getSetting('height'),
      '#placeholder' => t('Height')
    ];

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => $this->getSetting('title'),
      '#placeholder' => t('Title')
    ];


    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = t('Width: @rows', array('@rows' => $this->getSetting('width')));
    $summary[] = t('Height: @rows', array('@rows' => $this->getSetting('height')));
    $summary[] = t('Title: @rows', array('@rows' => $this->getSetting('title')));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => 290,
      'height' => 105,
      'title' => t("公司地址")
    ] + parent::defaultSettings();
  }

} // class