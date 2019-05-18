<?php

/**
 * @file
 * Contains \Drupal\epub\Plugin\Field\FieldFormatter\Epubjs.
 */

namespace Drupal\epub\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'epubJs' formatter.
 *
 * @FieldFormatter(
 *   id = "epub_js",
 *   label = @Translation("Epub: Epub.js reader"),
 *   field_types = {
 *     "file"
 *   }
 *  )
 */
class Epubjs extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    global $base_url;
    $elements = array();
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $file_url = file_create_url($file->getFileUri());
      if ($file->getMimeType() == 'application/epub+zip') {
        if ($this->getSetting('epub_unzipped')) {
          $path = file_create_url('public://epub_content/' . $file->id());
          $epub = $base_url . '/libraries/epub.js/reader/index.html?bookPath=' . $path . '/';
        } else {
          $epub = $base_url . '/libraries/epub.js/reader/index.html?bookPath=' . $file_url;
        }
        $file_url = file_create_url($file->getFileUri());
        $elements[$delta] = array(
            '#theme' => 'epub_formatter_js',
            '#file' => $file,
            '#reader' => $epub,
            '#width' => $this->getSetting('width'),
            '#height' => $this->getSetting('height'),
        );
      }
      else {
        $elements[$delta] = array (
            '#theme' => 'file_link',
            '#file' => $file,
        );
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['epub_unzipped'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use unzipped files from epub file in the reader'),
      '#default_value' => $this->getSetting('unzipped'),
      '#description' => t('If unchecked, zipped epub file will be used directly in the reader. Make sure your users\' browsers can handle zip file.'),
    );
    $element['epub_width'] = array(
      '#type' => 'textfield',
      '#title' => t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#description' => t('The width of ebook viewer area.'),
    );
    $element['epub_height'] = array(
      '#type' => 'textfield',
      '#title' => t('Height'),
      '#default_value' => $this->getSetting('height'),
      '#description' => t('The height of ebook viewer area. If "auto" is used, the iframe will auto-fit the epub document height and anchors in links will not work correctly.'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings['width'] = '100%';
    $settings['height'] = '600px';
    $settings['unzipped'] = TRUE;

    return $settings;
  }

}

