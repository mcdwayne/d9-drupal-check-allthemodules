<?php

namespace Drupal\zurb_clearing\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

class ZurbClearingFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = ['zurb_clearing_file_flag' => t('Original File')] + image_style_options(FALSE);

    $element = [
      '#type' => 'fieldset',
      '#title' => t('Zurb Clearing settings'),
    ];

    $element['image_style_thumb'] = [
      '#title' => t('Image thumbnail style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style_thumb'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    ];

    $element['image_style_large'] = [
      '#title' => t('Image large style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style_large'),
      '#empty_option' => t('None (open thumbnail in lightbox)'),
      '#options' => $image_styles,
    ];

    $element['featured'] = [
      '#title' => t('Feature the first image in the set'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('featured'),
      '#options' => [
        FALSE => 'No', TRUE => 'Yes'
      ],
    ];

    $element['show_captions'] = [
      '#title' => t('Show captions'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('show_captions'),
      '#options' => [
        FALSE => 'No', TRUE => 'Yes'
      ],
    ];

    // @todo Port caption field.
//    $fields = field_info_fields();
//    $options = array();
//
//    foreach ($fields as $name => $field) {
//      if (in_array('file', array_keys($field['bundles'])) && in_array('image', $field['bundles']['file']) && $field['type'] == 'text') {
//        $infos = field_info_instance('file', $name, 'image');
//        $options[$name] = t('File Entity field:') . ' ' . $infos['label'];
//      }
//    }
//
//    $element['caption_field'] = array(
//      '#title' => t('Caption field'),
//      '#type' => 'select',
//      '#default_value' => $this->getSetting('caption_field'),
//      '#empty_option' => t('None'),
//      '#options' => $options,
//    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if ($this->getSetting('image_style_thumb')) {
      $summary[] = t('Image thumbnail style: @value', ['@value' => $this->getSetting('image_style_thumb')]);
    } else {
      $summary[] = t('Image thumbnail style: not selected. Please select a thumbnail style.');
    }

    if ($this->getSetting('image_style_large')) {
      $summary[] = t('Image large style: @value', ['@value' => $this->getSetting('image_style_large')]);
    } else {
      $summary[] = t('Image large style: not selected. Please select an expanded large style.');
    }

    $summary[] = t('Feature the first image: @value', ['@value' => $this->getSetting('featured') ? 'Yes' : 'No']);
    $summary[] = t('Show captions: @value', ['@value' => $this->getSetting('show_captions') ? 'Yes' : 'No']);
    $summary[] = t('Caption field: @value', ['@value' => $this->getSetting('caption_field') ? $this->getSetting('caption_field') : 'not set']);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();
    $images = [];
    $element = [];

    if (count($items)) {
      foreach ($items as $item) {
        $item['path'] = $item['uri'];
        $image = [];

        if ($this->getSetting('image_style_thumb') && Unicode::strlen($this->getSetting('image_style_thumb'))) {
          $item['style_name'] = $this->getSetting('image_style_thumb');
          if ($this->getSetting('show_captions') && isset($item[$this->getSetting('caption_field')])) {
            $item['attributes']['data-caption'] = isset($item[$this->getSetting('caption_field')][$langcode][0]['value']) ? $item[$this->getSetting('caption_field')][$langcode][0]['value'] : '';
          }
          $image['thumbnail'] = ['#theme' => 'image_style'] + $item;
        }
        else {
          $image['thumbnail'] = ['#theme' => 'image'] + $item;
        }

        if ($this->getSetting('image_style_large') && Unicode::strlen($this->getSetting('image_style_large'))) {
          if ($this->getSetting('image_style_large') == 'zurb_clearing_file_flag') {
            $large_image = file_create_url($item['uri']);
          }
          else {
            $large_image = image_style_url($this->getSetting('image_style_large'), $item['uri']);
            $style = ImageStyle::load($this->getSetting('image_style_large'));
            $style->createDerivative($style, $item['uri'], $style->buildUrl($style['name'], $item['uri']));
          }
          $image['thumbnail'] = l($image['thumbnail'], $large_image, array('html' => TRUE));
        }

        $images[] = $image;
      }

      if ($this->getSetting('featured')) {
        $items[0]['featured'] = TRUE;
      }

      $element[] = [
        '#theme' => 'zurb_clearing',
        '#items' => $items,
        '#images' => $images,
        '#options' => [
          'image_style_thumb' => $this->getSetting('image_style_thumb'),
          'image_style_large' => $this->getSetting('image_style_large'),
          'show_captions'     => $this->getSetting('show_captions'),
          'caption_field'     => $this->getSetting('caption_field'),
          'featured'          => $this->getSetting('featured'),
        ],
        '#entity' => $entity,
      ];
    }

    return $element;
  }
}
