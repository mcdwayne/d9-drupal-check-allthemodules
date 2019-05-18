<?php
/**
 * @file PrincePDF
 *
 * Implements Prince PDF Generation
 */
namespace Drupal\forena_pdf\FrxPlugin\Document;
use Drupal\forena\FrxAPI;
use Drupal\forena\FrxPlugin\Document\DocumentBase;

/**
 * Provides PDF file exports using Prince XML
 *
 * @FrxDocument(
 *   id= "pdf-prince",
 *   name="PDF Generation using Prince XML",
 *   ext="pdf"
 * )
 */
class PrincePDF extends DocumentBase {
  use FrxAPI; 
  private $p;

  public function __construct() {
    include_once('libraries/prince/prince.php');
    $this->content_type='application/pdf';
    $prince_path = \Drupal::config('forena_pdf.settings')->get('prince_path');
    if (class_exists('\Prince') && forena_library_file('prince')) {
      $this->p = new \Prince($prince_path);
    }
  }

  /**
   * [@inheritdoc}
   */
  public function flush() {
    //@TODO: figure out how to deal with options
    $options = [];
    //@TODO: Figure out how to pass style portions of css doucments to the PDF.
    $css = '';
    $style_css = '';

    $disable_links = \Drupal::config('forena_pdf.settings')->get('disable_links');
    $html = $this->write_buffer;
    if ($disable_links) {
      $html = preg_replace('/<a href=\"(.*?)\">(.*?)<\/a>/', "\\2", $html);
    }

    $link_class = $disable_links ? 'prince-disable-links': '';
    $output = '<html><head>';
    $output .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
    if (!empty($options['css']) || isset($r->rpt_xml->head->style)) {
      $output .= '<style type="text/css">';
      $output .= $css;
      if ($style_css) {
        $sheet = (string)$r->rpt_xml->head->style;
        $output .= $sheet;
      }
      $output .= '</style>';
    }

    $output .= '<title>' . $this->title . "</title></head><body class='forena-report $link_class'><h1>" . $this->title . '</h1>' . $html . '</body></html>';
    $prince_css = drupal_get_path('module', 'forena_pdf') . '/forena_pdf_prince.css';
    // Generate the document
    if ($this->p) {
      $p = $this->p;
      foreach ($this->documentManager()->stylesheets as $type => $sheets) {
        foreach ($sheets as $sheet) {
          switch ($type) {
            case 'all':
            case 'print':
            case 'screen':
            case 'pdf':
              $p->addStyleSheet($sheet);
              break;
          }
        }
      }
      $msg = array();
      $pdf_file = tempnam(file_directory_temp(), 'prince_pdf');
      if ($p->convert_string_to_file($output, $pdf_file, $msg)) {
        $output = file_get_contents($pdf_file);
      }
      else {
        $this->app()->error('Could not generate PDF File', print_r($msg,1));
        $output = '';
      }
      // We don't care if this fails because it's temproary.
      @unlink($pdf_file);
      return $output;
    }
    else {
      $this->error(t('Prince XML Not Properly Installed'));
      return ('');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function header() {
    $this->headers['Content-Type'] = $this->content_type;
    $this->headers['Cache-Control'] = ''; 
    $this->headers['Pragma'] = '';
    $this->headers['Cache-Control'] =  'must-revalidate';
  }
}
