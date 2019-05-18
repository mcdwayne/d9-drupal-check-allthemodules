<?php
/**
 * @file HTML
 * Straight XML document with no wrapping theme.
 * @author davidmetzler
 *
 */
namespace Drupal\forena\FrxPlugin\Document;
use Drupal\forena\FrxAPI;

/**
 * XML Document
 *
 * Provide the report content as XML.  Only contents of the body will be
 * imported.
 *
 * @FrxDocument(
 *   id= "xml",
 *   name="XML Document",
 *   ext="xml"
 * )
 */
class XML extends DocumentBase {
  use FrxAPI;
  public $root_tag = 'div';
  public $root_attributes = array();

  public function __construct() {
    $this->allowDirectOutput = FALSE;
    $this->content_type = 'application/xml';
    $skin = $this->getDataContext('skin');
    if (isset($skin['XML']['rootElementName'])) {
      $this->root_tag = $skin['XML']['rootElementName'];
      if ($this->root_tag == 'none') $this->root_tag = '';
    }
    if (isset($skin['XML']['rootElementAttributes'])) {
      if (is_array($skin['XML']['rootElementAttributes'])) {
        $this->root_attributes = $skin['XML']['rootElementAttributes'];
      }
    }
  }

  public function header() {
    parent::header();
    $text = '<?xml version="1.0"?>' . "\n";
    $this->write($text);
    $opening_tag = $this->_build_opening_root_tag_contents();
    if ($this->root_tag) {
      $opening_tag="<$opening_tag>\n";
      $this->write($opening_tag);
    }
  }

  public function footer() {
    if ($this->root_tag) {
      $ending_tag = $this->root_tag;
      $ending_tag = "</$ending_tag>";
      $this->write($ending_tag);
    }
  }

  private function _build_opening_root_tag_contents() {
    $tag_contents = $this->root_tag;
    if (isset($this->root_attributes) && is_array($this->root_attributes)) {
      foreach ($this->root_attributes as $attr_key => $attr_val) {
        if (!empty($attr_key) && (string)$attr_val !=='') {
          $attr_val = addslashes($attr_val);
          $tag_contents .= " " . $attr_key . "=" . "'$attr_val'";
        }
      }
    }
    return $tag_contents;
  }
}
