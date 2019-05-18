<?php

/**
 * @file
 * Contains \Drupal\epub\Plugin\Field\FieldFormatter\EpubFormatterBase.
 */

namespace Drupal\epub\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'epub' formatter.
 *
 * @FieldFormatter(
 *   id = "epub",
 *   label = @Translation("Epub: Default"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class EpubFormatterBase extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $elements[$delta] = array(
        '#theme' => 'epub_formatter_default',
        '#file' => $file,
        '#width' => $this->getSetting('width'),
        '#height' => $this->getSetting('height'),
      );
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['epub_width'] = array(
      '#type' => 'textfield',
      '#title' => t('Width'),
      '#default_value' => $this->getSetting('width'),//$settings['width'] ? $settings['width'] : 'auto',
      '#description' => t('The width of ebook viewer area.'),
    );
    $element['epub_height'] = array(
      '#type' => 'textfield',
      '#title' => t('Height'),
      '#default_value' => $this->getSetting('height'),// $settings['height'] ? $settings['height'] : '100%',
      '#description' => t('The height of ebook viewer area. If "auto" is used, the iframe will auto-fit the epub document height and anchors in links will not work correctly.'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings['width'] = '100%';
    $settings['height'] = 'auto';
    return $settings;
  }

}


