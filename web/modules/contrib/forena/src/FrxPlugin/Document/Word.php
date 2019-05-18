<?php
/**
 * @file Word.inc
 * Word document exporter.
 * @author davidmetzler
 *
 */
namespace Drupal\forena\FrxPlugin\Document;

/**
 * Provides Microsoft Word
 *
 * HTML Documents are mime typed so that they open in MS word.
 *
 * @FrxDocument(
 *   id= "doc",
 *   name="HTML Mime typed as an MS Word document for importing into word",
 *   ext="doc"
 * )
 */
class Word extends DocumentBase {
  public function __construct() {
    $this->content_type = 'application/msword';
  }

  public function flush() {
    $output = '<html><head>';
    $output .= '<meta http-equiv="Content-Type" content="text/html"/>';
    $output .= '<title>' . $this->title . '</title></head><body class="forena-report">' . $this->write_buffer . '</body></html>';
    return $output;
  }

}
