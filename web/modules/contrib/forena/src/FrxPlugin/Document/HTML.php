<?php
/**
 * @file HTML
 * Straight html document with no wrapping theme.
 * @author davidmetzler
 *
 */
namespace Drupal\forena\FrxPlugin\Document;
/**
 * Provides Straight HTML page suitable for replacements
 *
 * @FrxDocument(
 *   id= "html",
 *   name="Unthemed HTML Page",
 *   ext="html"
 * )
 */
class HTML extends DocumentBase {
  public function header() {
    $this->headers = [];
    $this->headers['Content-Type'] = 'text/html ;charset='
      . $this->charset;
  }

  public function flush() {
    $css = '';
    $output = '<html><head>';
    $output .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
    $this->title;
      // @TODO: Add inline styles and css libraries
    if ($css) {
      $output = '<style type="text/css">';
      $output .= $css;
      /*
      if (isset($r->rpt_xml->head->style)) {
        $sheet = (string)$r->rpt_xml->head->style;
        $output .= $sheet;
      }
      */
      $output .= '</style>';
    }
    $output .= '<title>' . $this->title . '</title></head><body class="forena-report"><h1>' . $this->title . '</h1>' . $this->write_buffer . '</body></html>';
    return $output;
  }
}