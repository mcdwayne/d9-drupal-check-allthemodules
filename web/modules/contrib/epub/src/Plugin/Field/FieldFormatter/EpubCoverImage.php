<?php

/**
 * @file
 * Contains \Drupal\epub\Plugin\Field\FieldFormatter\EpubCoverImage.
 */

namespace Drupal\epub\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;


/**
 * Plugin implementation of the 'epub' formatter.
 *
 * @FieldFormatter(
 *   id = "epub_cover_image",
 *   label = @Translation("Epub: Cover Image"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class EpubCoverImage extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $file_url = file_create_url($file->getFileUri());
      if ($file->getMimeType() == 'application/epub+zip') {
        $elements[$delta] = array(
            '#theme' => 'epub_formatter_cover',
            '#file' => $file_url,
            '#width' => $this->getSetting('width'),
        );
        $dir = 'public://epub_content/' . $file->id();
        $scan = file_scan_directory($dir, '/.*\.opf$/');
        $opf = array_shift($scan);
        if (isset($opf)) {
          $opfXML = simplexml_load_file($opf->uri);
          $opfXML->registerXPathNamespace('opf', 'http://www.idpf.org/2007/opf');
          $element = $opfXML->xpath('/opf:package/opf:metadata/opf:meta[@name="cover"]');
          if (count($element)) {
            $attributes = $element[0]->attributes();
            $elements[$delta]['#image'] = file_create_url(epub_get_item($dir, (string)$attributes['content']));
          }
        }
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
    $element['epub_width'] = array(
      '#type' => 'textfield',
      '#title' => t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#description' => t('The width of ebook cover image.'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings['width'] = '100%';

    return $settings;
  }
}


