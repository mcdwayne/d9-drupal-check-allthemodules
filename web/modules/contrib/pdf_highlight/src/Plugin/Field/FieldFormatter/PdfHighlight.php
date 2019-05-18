<?php

namespace Drupal\pdf_highlight\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\pdf\Plugin\Field\FieldFormatter\PdfDefault;

/**
 * Plugin implementation of the 'pdf_highlight' widget.
 *
 * @FieldFormatter(
 *  id = "pdf_highlight",
 *  label = @Translation("PDF: Highlight text search with PDF.js"),
 *  description = @Translation("Use the default viewer like http://mozilla.github.io/pdf.js/web/viewer.html."),
 *  field_types = {"file"}
 * )
 */
class PdfHighlight extends PdfDefault {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'keep_pdfjs' => TRUE,
      'width' => '100%',
      'height' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      if ($item->entity->getMimeType() == 'application/pdf') {

        $file_url = file_create_url($item->entity->getFileUri());

        $iframe_src = file_create_url(base_path() . 'libraries/pdf.js/web/viewer.html');
        $iframe_src .= '?file=' . rawurlencode($file_url);

        $session = \Drupal::request()->getSession();
        $searchString = $session->get('pdf_highlight_search', 'default');
        if (!empty($searchString)) {
          $iframe_src .= '#search=' . $searchString;
        }

        $html = [
          '#theme' => 'file_pdf',
          '#attributes' => [
            'class' => ['pdf'],
            'webkitallowfullscreen' => '',
            'mozallowfullscreen' => '',
            'allowfullscreen' => '',
            'frameborder' => 'no',
            'width' => $this->getSetting('width'),
            'height' => $this->getSetting('height'),
            'src' => $iframe_src,
            'data-src' => $file_url,
          ],
        ];
        $elements[$delta] = $html;
      }
      else {
        $elements[$delta] = [
          '#theme' => 'file_link',
          '#file' => $item->entity,
        ];
      }
    }

    $elements['#attached']['library'][] = 'pdf_higlight/pdf_highlight';
    $elements['#cache']['max-age'] = 0;

    if ($this->getSetting('keep_pdfjs') != TRUE) {
      $elements['#attached']['library'][] = 'pdf/default';
    }
    return $elements;
  }

}
