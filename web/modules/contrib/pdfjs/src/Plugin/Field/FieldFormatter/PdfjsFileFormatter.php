<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\field\formatter\PdfjsFileFormatter.
 */

namespace Drupal\pdfjs\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'file_default' formatter.
 *
 * @FieldFormatter(
 *   id = "file_pdfjs",
 *   label = @Translation("PDF.JS display"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class PdfjsFileFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'width' => '',
      'height' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['width'] = array(
      '#title' => t('Width'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('width'),
    );

    $element['height'] = array(
      '#title' => t('Height'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('height'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $width = $this->getSetting('width');
    $height = $this->getSetting('height');
    if ($width && $height) {
      $summary[] = t('Display PDF with size @width x @height', array(
        '@width' => $width,
        '@height' => $height,
      ));
    }
    else {
      $summary[] = t('Display PDF with size original size');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if ($item->isDisplayed() && $item->entity) {
        $elements[$delta] = array(
          '#theme' => 'file_pdfjs',
          '#file' => $item->entity,
          '#description' => $item->description,
        );
        // Pass field item attributes to the theme function.
        if (isset($item->_attributes)) {
          $elements[$delta] += array('#attributes' => array());
          $elements[$delta]['#attributes'] += $item->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }
      }
    }
    $elements['#attached']['library'][] = 'pdfjs/pdfjs';
    $elements['#attached']['library'][] = 'pdfjs/drupal.pdfjs';
    $elements['#attached']['js'][] = array(
      'data' => array(
        'pdfjs' => array(
          'width' => $this->getSetting('width'),
          'height' => $this->getSetting('height'),
          'basePath' => drupal_get_path('module', 'pdfjs') . '/assets/vendor/pdf.js/build'
        ),
      ),
      'type' => 'setting'
    );

    return $elements;
  }

}
