<?php
/**
 * @file FrxInclude
 * Include a reference to another report as an asset.
 * @author davidmetzler
 *
 */
namespace Drupal\forena\Template;
use SimpleXMLElement;
class FrxInclude extends TemplateBase {
  
  public function scrapeConfig(\SimpleXMLElement $xml) {
    return []; 
  }

  /**
   * Implement template generator.
   * @see FrxRenderer::generate()
   */
  public function generate() {
    $src = $this->extract('src', $key);
    $div = $this->blockDiv($config);
    $frx = array('src' => $src, 'renderer' => 'FrxInclude');
    $this->addNode($div, 4, 'div', NULL, $config, $frx);
  }

}