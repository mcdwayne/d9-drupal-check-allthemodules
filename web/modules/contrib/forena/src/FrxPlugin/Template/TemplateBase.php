<?php
/**
 * @file FrxRenderer.php
 * Base class for FrxAPI custom Renderer
 * @author davidmetzler
 *
 */
namespace Drupal\forena\Template;
use Drupal\forena\AppService;
use Drupal\forena\DataManager;
use DOMXPath;
use DOMElement;
use Drupal\forena\FrxAPI;
use Drupal\forena\Report;
use DOMNode;
abstract class TemplateBase implements TemplateInterface {
  use FrxAPI;
  /** @var  array Confiuration of template */
  public $configuration;
  public $name;
  public $id;
  public $columns;
  public $numeric_columns;
  public $xmlns = 'urn:FrxReports';

  public function configure($config) {
    $this->configuration = $config;
  }


  /**
   * Return the inside xml of the current node
   * @param \SimpleXMLElement $xml
   *   XML Node of report containing block.
   * @return string 
   *   String representation of node children. 
   */
  public function innerXML(\SimpleXMLElement $xml) {
    $tag = $xml->getName();
    $text = '';
    if (is_object($xml) && is_object($xml->$tag)) {
      $text = $xml->asXML();
      $text = preg_replace("/<\/?" . $tag . "(.|\s)*?>/", "", $text);
    }
    return $text;
  }


  /**
   * Extract a list of columns from the data context.
   * @param \SimpleXMLElement $xml The xml data
   * @param string $path
   *   Xpath used to determine the columns
   * @return array 
   *   Data columns or fields found in data. 
   */
  public function columns(\SimpleXMLElement $xml, $path='/*/*') {
    //create an array of columns
    if (!is_object($xml)) return array();
    // Use xpath if possible otherwise iterate.
    if (method_exists($xml, 'xpath')) {
      $rows = $xml->xpath($path);
    }
    else {
      $rows = $xml;
    }
    $column_array = array();
    $numeric_columns = array();
    foreach ($rows as $columns) {
      foreach ($columns as $name => $value) {
        $label = str_replace('_', ' ', $name);
        $column_array[$name] = $label;
        if (is_numeric((string)$value)) {
          $numeric_columns[$name] = $label;
        }
        else {
          if (isset($numeric_columns[$name])) unset($numeric_columns[$name]);
        }
      }
      if (is_object($xml) && method_exists($xml, 'attributes')) {
        foreach ($xml->attributes() as $name => $value) {
          $column_array['@' . $name] = '@' . $name;
        }
      }
    }
    $this->columns = $column_array;
    $this->numeric_columns = $numeric_columns;
    return $column_array;
  }

}