<?php
namespace Drupal\forena\Template;
use Drupal\forena\FrxPlugin\Document\XML;
use Drupal\forena\Report;

class FrxSection extends TemplateBase {

  public $templateName = 'Section';
  public $lastClass = '';

  private $template = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE root [
<!ENTITY nbsp "&#160;">
]>
<html xmlns:frx="urn:FrxReports">
<head>
<body>
  <div class="FrxSection">
  {template}
  </div>
</body>
</html>
EOF;

  /**
   * [@inheritdoc}
   */
  public function scrapeConfig(\SimpleXMLElement $xml) {
    // Simple section template
    $template = $this->innerXML($xml);
    $this->configuration['template'] = $template;
  }

  /**
   * Generate configuration.
   */
  public function configForm() {
    $config = $this->configuration;
    $form['sections'] = array('#theme' => 'forena_element_draggable',   '#draggable_id' => 'FrxContainer-sections');
    if (isset($config['sections'])) foreach ($config['sections'] as $id => $section) {
      $ctl = array();
      $ctl['id'] = array('#type' => 'item', '#markup' => $id, '#title' => 'id');
      $ctl['markup'] = array('#type' => 'value', '#value' => $section['markup']);
      $ctl['class_label'] = array('#type' => 'item', '#markup' => @$section['class'], '#title' => t('Type'));
      $ctl['class'] = array('#type' => 'value', '#value' => @$section['class']);
     // $ctl['display'] = array('#type' => 'item', '#title' => 'html',  '#markup' =>$section['markup']);
      $form['sections'][$id] = $ctl;
    }
    return $form;
  }

  /**
   * Validate the configuration
   */
  public function configValidate(&$config) {
  }

  /**
   * Build document from the existing template.
   * @param $xml
   * @param $config
   * @return string 
   *   Report fragment. 
   */
  public function generate() {
    $doc = new XML();
    $this->pushData($this->configuration, '_template');
    $report = new Report($this->template, $doc);
    $report->render();
    $text = $doc->flush();
    $xml = new \SimpleXMLElement($text);
    return $this->innerXML($xml);
  }
}