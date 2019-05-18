<?php
/**
 * @file PrincePDF
 *
 *
 */
namespace Drupal\forena_pdf\FrxPlugin\Document;
use Drupal\forena\FrxPlugin\Document\DocumentBase;

/**
 * Provides PDF file exports using Doc Raptor PDF Generation Service
 *
 * @FrxDocument(
 *   id= "pdf-docraptor",
 *   name="PDF Generation using Doc Raptor web services",
 *   ext="pdf"
 * )
 */
class DocRaptorPDF extends DocumentBase {
  private $docraptor_key; 
  private $docraptor_url; 
  private $docraptor_test; 

  public function __construct() {
    $this->content_type='application/pdf';
    $config = \Drupal::config('forena_pdf.settings'); 
    $this->docraptor_key = $config->get('docraptor_key'); 
    $this->docraptor_url = $config->get('docraptor_url'); 
    $this->docraptor_test = $config->get('docraptor_test');
  }

  public function flush() {
    $options = [];
    $css = '';
    $disable_links = \Drupal::config('forena.pdf.settings')->get('disable_links');
    $html = $this->write_buffer;
    if ($disable_links) {
      $html = preg_replace('/<a href=\"(.*?)\">(.*?)<\/a>/', "\\2", $html);
    }

    $link_class = $disable_links ? 'prince-disable-links': '';
    $output = '<html><head>';
    $output .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
    if (isset($options['css']) || isset($r->rpt_xml->head->style)) {
      $output .= '<style type="text/css">';
      $output .= $css;
      if (isset($r->rpt_xml->head->style) || isset($r->rpt_xml->head->style)) {
        $sheet = (string)$r->rpt_xml->head->style;
        $output .= $sheet;
      }
      $output .= '</style>';
    }

    $output .= '<title>' . $this->title . "</title></head><body class='forena-report $link_class'><h1>" . $this->title . '</h1>' . $html . '</body></html>';

    $api_key = $this->docraptor_key;
    if ($api_key) {

      $service_url = $this->docraptor_url;
      $url = "$service_url?user_credentials=$api_key";

      $name = 'report.pdf';
      $post_array = array(
        'doc[name]' => $name,
        'doc[document_type]' => 'pdf',
        'doc[test]' => $this->docraptor_test ? 'true' : 'false',
        'doc[document_content]' => $output,
      );

      $postdata = http_build_query($post_array);

      $opts = array('http' =>
        array(
          'method'  => 'POST',
          'header'  => 'Content-type: application/x-www-form-urlencoded',
          'content' => $postdata
        )
      );

      $context = stream_context_create($opts);

      $doc = file_get_contents($url, false, $context);

      return $doc;
    }
    else {
      drupal_set_message(t('No Docraptor API Key Configured'), 'error');
      return ('');
    }
  }

}
