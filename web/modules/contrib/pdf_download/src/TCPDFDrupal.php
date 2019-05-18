<?php

namespace Drupal\pdf_download;
use TCPDF;
/**
 * Do not create a new instance of this class manually.Use tcpdf_get_instance().
 * @see tcpdf_get_instance()
 */

class TCPDFDrupal extends TCPDF {
  function __construct($orientation='P', $unit='mm', $format='A4', $unicode=TRUE, $encoding='UTF-8', $diskcache= FALSE, $pdfa= FALSE) {
    parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
  }

  protected $drupalHeader = array(
    'html' => NULL,
    'callback' => NULL,
  );
  protected $drupalFooter = array(
    'html' => NULL,
    'callback' => NULL,
  );

  function DrupalInitialize($options) {
    $site_name = \Drupal::config('system.site')->get('name');
    $title = isset($options['title']) ? $options['title'] : $site_name;
    $author = isset($options['author']) ? $options['author'] : $site_name;
    $subject = isset($options['subject']) ? $options['subject'] : $site_name;
    $keywords = isset($options['keywords']) ? $options['keywords'] : 'pdf, drupal';
    $this->drupalHeader = isset($options['header']) ? $options['header'] : $this->drupalHeader;
    $this->drupalFooter = isset($options['footer']) ? $options['footer'] : $this->drupalFooter;
    $this->SetCreator(PDF_CREATOR);
    $this->SetAuthor($author);
    $this->SetTitle($title);
    $this->SetSubject($subject);
    $this->SetKeywords($keywords);
    $this->setFooterFont(Array(PDF_FONT_NAME_DATA, '', 6));
    $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $this->SetMargins(PDF_MARGIN_LEFT, 28, PDF_MARGIN_RIGHT);
    $this->SetHeaderMargin(PDF_MARGIN_HEADER);
    $this->SetFooterMargin(PDF_MARGIN_FOOTER);
    $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
     $this->SetFont('dejavusans', '', 8);
    $this->AddPage();
  }

  /**
   * Sets the header of the document.
   * @return NULL
   */
  public function Header() {
    if (!$this->DrupalGenRunningSection($this->drupalHeader)) {
      return parent::Header();
    }
  }

  /**
   * Sets the footer of the document.
   * @return NULL
   */
  public function Footer() {
    if (!$this->DrupalGenRunningSection($this->drupalFooter)) {
      return parent::Footer();
    }
  }

  /**
   * Generates a header or footer for the pdf document.
   *
   * @param array $container
   * @see DrupalInitialize()
   *
   * @return FALSE if the container did not store any useful information to generate
   *   the document.
   */
  private function DrupalGenRunningSection($container) {
    if (!empty($container['html'])) {
      $this->writeHTML($container['html']);
      return TRUE;
    }
    elseif (!empty($container['callback'])) {
      $that = &$this;
      if (is_array($container['callback'])) {
        if (function_exists($container['callback']['function'])) {
          call_user_func($container['callback']['function'], $that, $container['callback']['context']);
        }
      }
      elseif (function_exists($container['callback'])) {
        call_user_func($container['callback'], $that);
      }
      return TRUE;
    }
    return FALSE;
  }
}
