<?php
/**
 * @file
 * Implements \Drupal\forena\Context\DataContext
 */
namespace Drupal\forena\Context;
use Drupal\forena\AppService;
use SimpleXMLElement;
use DOMDocument;
use DOMXPath;

/**
 * The DataContext class holds all of the data contexts during the report
 * rendering process. The general idea is that during the report render, data
 * objects are pushed on the stack with the id's of the block or foreach objects
 * that invoke them.
 *
 * Rendering controls may then get current data contexts, and push data onto the
 * stack or pop data onto the stack. They may also use this method to get the
 * current data context from the stack.
 */
class DataContext {

  public $id;
  private $cur_context;  // The data of the xml;
  private $cur_context_xml; //
  private $data_sources = array();
  private $data_stack = array();
  private $id_stack = array();

  /**
   * Return the current data context
   *
   */
  public function currentContext() {
    return $this->cur_context;
  }

  /**
   * Return current context as key value pairs
   * Enter description here
   */
  public function currentContextArray() {
    $data = $this->cur_context;
    if (is_array($data)) {
      return $data;
    }

    if (is_object($data)) {
      // Get attributes
      $ret = get_object_vars($data);
      if (method_exists($data, 'attributes')) {
        foreach ($data->attributes() as $key => $value) {
          $ret[$key] = (string)$value;
        }
      }

    }
    else {
      $ret = (array)$data;
    }
    return $ret;

  }

  /**
   * Provides an api to the {=xpath} syntax that can be used
   * to evaluate expressions such as sum and count in a report.  We
   * need to use the DOM object here, because this method isn't exposed
   * with simplexml.
   *
   * @param \SimpleXMLElement $xml
   * @param string $path
   * @return \SimpleXMLElement
   */
  protected function simplexml_evaluate($xml, $path) {
    if (!method_exists($xml, 'xpath')) return '';
    $dom_node = dom_import_simplexml($xml);
    $dom_doc = new DOMDocument('');
    $dom_node = $dom_doc->importNode($dom_node, TRUE);
    $dom_doc->appendChild($dom_node);
    // Do we also need to call AppendChild?
    $xpath = new DOMXpath($dom_doc);
    $ret = $xpath->evaluate($path, $dom_node);
    return $ret;
  }


  static public function arrayToXml($a, &$xml=NULL) {
    if (!$xml) $xml = new SimpleXMLElement('<root/>');
    $tag = '';
    foreach($a as $k => $v) {
      if (preg_match('/^[0-9\-\.]/', $k)) {
        if (!$tag) $tag = "element";
      }
      else {
        $tag = $k;
      }
      if (is_array($v) || is_object($v)) {
        $node = $xml->addChild($tag, '');
        if ($tag != $k) {
          $node['key'] = $k;
        }
        $node['type'] = is_object($v) ? 'object' : 'array';
        DataContext::arrayToXml($v, $node);
      }
      else {
        $node = $xml->addChild($tag, htmlspecialchars($v));
        $node['key'] = $k;
      }

      $tag =  preg_replace( '/[^a-zA-Z0-9]/', '_', (string) $k);
    }
    return $xml;
  }

  /**
   * Get the value from the data.
   * This is used by token_replace method to extract the data based on the path provided.
   * @param $key
   *   The key/tken to search for.
   * @param string $context
   *   The id of the context to get the value from.
   * @return mixed
   *   Value or array returned for replacement
   */
  public function getValue($key, $context = '') {
    $retvar = '';
    // Default to theo current context
    $data = $this->currentContext();

    if ($context && $this->contextExists($context)) {

      $data = $this->getContext($context);

    }
    if (!preg_match('/[\=\@\[\/\]\(\)]/', $key)) {
      if (is_array($data)) {
        $retvar = @$data[$key];
      }
      elseif (is_object($data)) {
        $retvar = $data->$key;
      }
    }
    elseif (is_object($data) || is_array($data)) {
      if (is_array($data) ) {
        if ( !$this->cur_context_xml) $this->cur_context_xml = DataContext::arrayToXml($data);
        $data = $this->cur_context_xml;
      }
      elseif (!method_exists($data, 'xpath')) {
        if(method_exists($data, 'asXML')) {
          $xml = $data->asXML();
          if (!is_object($xml) && $xml) $xml = new SimpleXMLElement($xml);
          $this->cur_context_xml = $xml;
        }
      }

      if (strpos($key, '=')===0) {
        $retvar = $this->simplexml_evaluate($data, ltrim($key, '='));
      }
      else {
        $x ='';
        if (isset($data->$key)) {
          $x = $data->$key;
        }
        elseif (method_exists($data, 'xpath')) {
          $rows = @$data->xpath($key);
          if ($rows === FALSE) {
            drupal_set_message(t('Invalid field: "%s"', array('%s' => $key)), 'error', FALSE);
          }

          if ($rows) $x = $rows[0];
        }

        if ($x && is_object($x) && method_exists($x, 'asXML')) {
          $retvar = $x->asXML();
          // Check to see if there are child nodes
          // If so use asXML otherwise string cast.
          if ($retvar && strpos($retvar, '<')!==FALSE) {
            // Find the end of the first tag.
            $p = strpos($retvar, '>');
            $retvar = substr_replace($retvar, '', 0, $p+1);
            $p = strrpos($retvar, '<', -1);
            $retvar = substr_replace($retvar, '', $p, strlen($retvar) - $p);

          }
          else {
            $retvar = (string)$x;
          }
        }
        else {
          $retvar = &$x;
        }
      }
    }

    if (!is_array($retvar)) {
      if (is_object($retvar) && is_a($retvar, 'DOMNodeList')) {
        $retvar = $retvar->item(0);
        if ($retvar) ($retvar = trim($retvar->textContent));
      }
      else {
        $retvar = trim((string)$retvar);
      }
    }
    return $retvar;
  }

  /**
   * Allows override of a value for the current context.
   * @param string $key
   *   Poperty key to set.
   * @param string $value
   *   Property Value to set
   * @param string $context
   *   Value of context to set.
   */
  public function setValue($key, $value, $context='') {
    if (is_array($this->cur_context)) {
      $this->cur_context[$key] = $value;
      if ($this->cur_context_xml) $this->cur_context_xml->$key = $value;
    }
    elseif (is_object($this->cur_context)) {
      if (strpos($key, '@')===0) {
        $this->cur_context[trim($key, '@')] = $value;
      }
      else {
        $this->cur_context->$key = $value;
      }
    }
  }

  /**
   * Push a data context onto the data stacks
   * to make sure that we can address these using an
   * appropriate syntax.  I think we don't need data_stack
   * but i'm holding it there in case we develop a "relative" path syntax.
   * @param $data
   * @param $id
   */
  public function push($data, $id='') {
    $this->data_stack[] = $this->cur_context;
    $this->id_stack[] = $this->id;
    $this->id = $id;
    $this->cur_context = $data;
    $this->cur_context_xml = '';

    if ($id) {
      /*    if (@is_array($this->data_sources[$id]) && is_array($data)) {
       $data = array_merge($this->data_sources[$id], $data);
       }
       */
      $this->data_sources[$id] = $data;
    }
  }

  /**
   * @param $id
   *   Id of the data context to be set
   * @param $data
   *   Data of the data context to be set.
   */
  public function setContext($id, &$data) {
    if (is_object($data)) {
      $this->data_sources[$id] = $data;
    }
    else {
      $this->data_sources[$id] = &$data;
    }
  }

  /**
   * Remove data from the data stack.
   *
   * This will make data unavaiable when we leave the context of the current
   * nested reports.
   */
  public function pop() {
    $this->id = array_pop($this->id_stack);
    $this->cur_context = array_pop($this->data_stack);
    $this->cur_context_xml = '';
  }

  /**
   * Determines whether an array context exists for the specified id.
   * Returns true if the key exists othewise false
   * @param string $id
   * @return bool
   */
  public function contextExists($id) {

    $exists = FALSE;
    if (array_key_exists($id, $this->data_sources)) {
      $exists = TRUE;
    }
    else {
      // Check for module provided contexts;
      $contexts = AppService::instance()->getContextPlugins();
      if (isset($contexts[$id])) {
        $class = $contexts[$id];
        $object = new $class();
        $this->setContext($id, $object);
        $exists = TRUE;
      }
    }
    return $exists;
  }

  /**
   * Return a data context by id.
   *
   * @param string $id
   * @return \SimpleXMLElement | array
   *   Data Context
   */
  public function getContext($id) {
    return @$this->data_sources[$id];
  }

  public function dumpContext() {
    AppService::instance()->debug('cur_context ' . $this->id,
      '<br/>Stack<br/><pre>' . print_r($this->cur_context, 1) . '</pre>');
  }
  
}