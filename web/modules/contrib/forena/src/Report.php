<?php
// $Id$
/**
 * @file
 * Basic report provider.  Controls the rendering of the report.
 */
namespace Drupal\forena;

use Drupal\forena\Context\DataContext;
use Drupal\forena\Token\ReportReplacer;
use \DOMDocument;
use \DOMXPath;
use \SimpleXMLElement;

class Report {
  // Advertise the api.
  use FrxAPI;
  const FRX_NS = 'urn:FrxReports';
  // Data manager service
  public $blocks_loaded;
  /** @var  \SimpleXMLElement */
  public $rpt_xml;
  public $category;
  public $cache;
  public $form;
  public $access;
  public $parameterDefinitions;
  // Ajax Commands
  public $commands; 
  public $descriptor;
  public $options;
  public $formats;
  public $doctypes; 
  public $title;
  public $frx_title;
  public $data;
  public $body;
  public $html;
  public $skin;
  public $replacer;
  public $parms;
  public $missing_parms = FALSE;
  /** @var  \DomDocument */
  public $dom;
  public $xpathQuery;
  public $format;
  public $allowDirectWrite = FALSE; //Determine whether we can output directly.
  protected $renderers= [
    'FrxCrosstab' => '\Drupal\forena\FrxPlugin\Renderer\FrxCrosstab',
    'FrxInclude' => '\Drupal\forena\FrxPlugin\Renderer\FrxInclude',
    'FrxMyReports' => '\Drupal\forena\FrxPlugin\Renderer\FrxMyReports',
    'FrxParameterForm' => '\Drupal\forena\FrxPlugin\Renderer\FrxParameterForm',
    'FrxSource' => '\Drupal\forena\FrxPlugin\Renderer\FrxSource',
    'FrxSVGGraph' => '\Drupal\forena\FrxPlugin\Renderer\FrxSVGGraph',
    'FrxTemplate' => '\Drupal\forena\FrxPlugin\Renderer\FrxTemplate',
    'FrxTitle' => '\Drupal\forena\FrxPlugin\Renderer\FrxTitle',
    'FrxXML' => '\Drupal\forena\FrxPlugin\Renderer\FrxXML',
    'RendererBase' => '\Drupal\forena\FrxPlugin\Renderer\RendererBase',
  ];
  public $controls=[];
  private $sortCriteria;
  private $compareType;


  public function __construct($xhtml='') {
    $this->renderers = AppService::instance()->getRendererPlugins();
    $this->access = array();
    $this->parameterDefinitions = array();
    $this->options = array();
    $this->replacer = new ReportReplacer($this);
    $this->input_format = AppService::instance()->input_format;
    $this->skin = AppService::instance()->default_skin;
    if ($xhtml) {
      $dom = $this->dom = new DOMDocument('1.0', 'UTF-8');

      // Load document and simplexml representation
      try {
        $success = $dom->loadXML($xhtml);
      }
      catch(\Exception $e) {
        return NULL; 
      }
      if (!$success) return NULL; 

      $this->xpathQuery = new DOMXPath($dom);
      $this->setReport($dom, $this->xpathQuery);
    }



  }

  /**
   * Unset properites for better memory management.
   */
  public function __destruct() {
    // Empty Renderer controls

    // Empty properties.
    foreach ($this as $key => $value) {
      unset($this->$key);
    }
  }

  public function getRenderer($renderer='') {
    if (!$renderer || !isset($this->renderers[$renderer])) {
      $renderer = 'RendererBase';
    }
    // Build array of controls.
    if (!isset($this->controls[$renderer])) {
      $class = $this->renderers[$renderer];

      $c = new $class($this);
      $this->controls[$renderer] = $c;
    }
    return $this->controls[$renderer];
  }

  public function setParameterDefinitions($parms) {
    $this->data = $parms;
  }

  // Return the access attributes for the data block.
  public function getBlockAccess() {
    $repos = []; 
    $security = [];
    $block_xml = $this->rpt_xml->xpath('//*[@frx:block]');
    // Extract all the blocks and organize by provider

    foreach ($block_xml as $key => $block_node) {
      $attrs = $block_node->attributes('urn:FrxReports');
      foreach ($attrs as $key => $value) {
        if ($key == 'block') {
          @list($provider, $block) = explode('/', $value, 2);
          $repos[$provider][] = $block;
        }
      }
    }

    if ($repos) {
      foreach ($repos as $provider_key => $blocks) {
        $provider = $this->dataManager()->repository($provider_key);
        $access = array();
        foreach ($blocks as $block_name) {
          if ($provider && $block_name) {
            if (method_exists($provider, 'loadBlock')) {
              $block = $provider->loadBlock($block_name);
              $obj = @$block['access'];
              if (array_search($obj, $access) === FALSE) {
                $access[] = $obj;
              }
            }
          }
        }
        $security[$provider_key] = $access;
      }
    }
    return $security;
  }

  /**
   * Sets the report.
   * @param DOMDocument $dom
   *   Root Document element for the report
   * @param DOMXPath $xpq
   *   Query object associated with the element
   */
  public function setReport(DOMDocument $dom, DOMXPath $xpq) {
    $this->dom = $dom;
    $dom->formatOutput = TRUE;
    $this->xpathQuery = $xpq;

    /** @var \SimpleXMLElement $rpt_xml */
    $rpt_xml = $this->rpt_xml = simplexml_import_dom($this->dom->documentElement);
    $this->missing_parms = FALSE;

    // Load header data
    $this->body = $rpt_xml->body;
    if ($rpt_xml->head) {
      $this->title = (string)$rpt_xml->head->title;

      /** @var \SimpleXMLElement $nodes */
      $nodes = $rpt_xml->xpath('//frx:docgen/frx:doc');
      $this->formats = array();


      if ($nodes) {
        /** @var \SimpleXMLElement $value */
        foreach ($nodes as $value) {
          $arr = $value->attributes();
          $this->formats[] = (string)$arr['type'];
        }
      }

      $security = [];
      /** @var \SimpleXMLElement $node */
      foreach ($rpt_xml->head->children(Report::FRX_NS) as $name => $node) {
        switch ($name) {
          case 'access':
            $access = trim((string)$node);
            if (strpos($access, '.')){
              list($repos, $right) = explode('.', $access, 2);
              $security[$repos][] = $right;
            }
            break;
        	case 'fields':
        	  $field_nodes = $node->xpath('frx:field');
            $fields = $this->extractDefinitions($field_nodes, 'id', 'default');
            foreach ($fields as $key => $field) {
              $this->replacer->defineField($key, $field);
            }
            break;
          case 'category':
            $this->category = (string)$node;
        	  break;
        	case 'options':
        	  foreach ($node->attributes() as $key => $value) {
        	    $this->options[$key] = (string)$value;
        	  }
        	  break;
        	case 'cache':
        	  foreach ($node->attributes() as $key => $value) {
        	    $this->cache[$key] = (string)$value;
        	  }
            break;
        	case 'title':
        	  $this->frx_title = (string)$node;
        	  break;
        	case 'parameters':
            /** @var  \SimpleXMLElement $parm_node */
            foreach ($node->children(Report::FRX_NS) as $key => $parm_node) {
        	    $parm = array();
        	    foreach ($parm_node->attributes() as $akey => $attr) {
        	      $parm[$akey] = (string)$attr;
        	    }
        	    $id = $parm['id'];
        	    $val = isset($parm['value']) ? $parm['value'] : '';
        	    $parm['value']= ((string)$parm_node) ? (string)$parm_node : $val;
              // Convert pipes to an array.
              if ($parm['value'] && strpos($parm['value'], '!' !== FALSE)) {
                $parm['value'] = explode('|', $parm['value']);
              }
        	    $this->parameterDefinitions[$id] = $parm;
        	  }
        	  break;
          case 'commands':
            /** @var  \SimpleXMLElement $ajax_node */
            $events =  $node->attributes();
            $event = $events['event'];
            $event = strpos($event, 'pre') === 0 ? 'pre' : 'post'; 
            foreach ($node->children(Report::FRX_NS) as $key => $ajax_node) {
              $command = array();
              $command['text'] = $this->innerXML($ajax_node, 'frx:ajax');
              foreach ($ajax_node->attributes() as $akey => $attr) {
                $command[$akey] = (string) $attr;
              }
              $this->commands[$event][] = $command;
            }
            break; 
        	case 'data':
        	  $data = array();
        	  foreach($node->attributes() as $key => $value) {
        	    $data [$key] = (string)$value;
        	  }
            $data['data'] = $node;
        	  $this->data[] = $data;
        	  break;
        	case 'doctypes':
        	  $this->doctypes = $node;
        	  break;
          case 'skin':
            $def = [];
            $skin_definition = (string)$node;
            if ($skin_definition) {
              try {
                $def = Skin::parseJSON($skin_definition);
              }
              catch (\Exception $e) {
                $this->error('Unable to parse JSON', $e->getMessage());
              }
              Skin::instance($this->skin)->merge($def);
            }
            break;
        }

      }

      if (empty($security)) {
        $security = $this->getBlockAccess();
      }
      $this->access = $security;
      if (!empty($this->options['skin'])) {
        $this->skin = $this->options['skin'];
      }
    }
  }

  /**
   * Get default parameters from report.
   * @return array
   */
  public function getDefaultParameters() {
    $defaults = [];
    foreach ($this->parameterDefinitions as $key => $def) {
      if ( $def['value']) {
        $defaults[$key] = $def['value'];
      }
    }
    return $defaults;
  }

  /**
   * Get the data block
   * @param string $block
   * @param string $data_uri
   * @param bool $raw_mode
   *
   * @return \SimpleXMLElement | array
   */
  public function getData($block, $data_uri='', $raw_mode=FALSE) {
    $dm = DataManager::instance();
    $data = array();
    if ($data_uri) {
      parse_str($data_uri, $data);
      if (is_array($data)) foreach ($data as $key => $value) {
        $data[$key] = $this->replacer->replace($value, TRUE);
      }
    }
    $xml = $dm->data($block, $raw_mode, $data);
    if ($xml) {
      $this->blocks_loaded = TRUE;
    }
    return $xml;
  }

  /**
   * Collapse the parameters if the data is loaded.
   */
  public function collapseParameters() {
    if (is_array($this->getDocument()->parameters_form)
        && $this->getDocument()->parameters_form) {
      $form = $this->getDocument()->parameters_form;
      if (isset($form['params']) && @$form['params']['#collapsible'])  {
        $this->getDocument()->parameters_form['params']['#collapsed'] = $this->blocks_loaded;
      }
    }
  }

  public function preloadData() {
    $blocks_loaded = $this->blocks_loaded;

    $jsonData = array();
    if ($this->data) foreach ($this->data as $d) {
      if (!empty($d['block'])) {
        $id = @$d['id'];
        $block = $d['block'];
        $parms = @$d['parameters'];
        $raw = !empty($d['json']) || !empty($d['raw_mode']);

        /** @var \SimpleXMLElement $data */
        $data = $this->getData($block, $parms, $raw);
        if (@$d['path'] && !$raw && $data) {
          $data = $data->xpath((string)$d['path']);
          if ($data) $data = $data[0];
        }
        $this->setDataContext($id, $data);
        if (!empty($d['json']) && $this->format == 'web') {
          $ret = array();
          if ($data) foreach($data as $row) {
            $ret[] = $row;
          }
          $jsonData[$d['json']] = $ret;
        }
      }
      else {
        $id = @$d['id'];
        if ($id && isset($d['data'])) $this->setDataContext($id, $d['data']);
      }
    }

    if ($jsonData) {
      drupal_add_js(array('forenaData' => $jsonData), 'setting');
    }
    $this->blocks_loaded = $blocks_loaded;
  }

  /**
   * Render the report
   */
  public function render($format, $render_form=TRUE, $cache_data=array()) {
    /** @var \DOMDOcument $dom */
    $dom = $this->dom;
    // Trap error condition
    if (!$dom) return;
    $body = $dom->getElementsByTagName('body')->item(0);
    $this->preloadData();
    // Render the rport.
    /** @var \Drupal\forena\FrxPlugin\Renderer\RendererBase $c */
    $c = $this->getRenderer();
    $c->initReportNode($body);
    if (!$this->missing_parms) $c->renderChildren($body, $this->html);

    // Determine the correct filter.
    $filter = $this->input_format;
    $skinfo = $this->getDataContext('skin');
    if (isset($skinfo['input_format'])) $filter = $skinfo['input_format'];
    if (isset($this->options['input_format'])) $filter = $this->options['input_format'];
    if ($filter && $filter != 'none') {
      $this->html = check_markup($this->html, $filter);
    }

    // Default in dynamic title from head.
    if ($this->frx_title) {
      $title = check_plain($this->replacer->replace($this->frx_title));
      if ($title) $this->title = $title;
    }
    
    // Process the commands after the replacement
    if ($this->commands) {
      foreach($this->commands as $event => $commands) {
        foreach($commands as $command) {
          $this->getDocument()->addAjaxCommand($command, $event);
        }
      }
    }
  }

  public function getField($id) {
    $field = array_fill_keys(array('default', 'link', 'add-query', 'class', 'rel', 'format', 'format-string', 'target', 'calc', 'context'), '');
    if ($this->fields) {
      $path = 'frx:field[@id="' . $id . '"]';
      $formatters = $this->fields->xpath($path);
      if ($formatters) {
        $formatter = $formatters[0];
        //@TODO: Replace the default extraction with something that will get sub elements of the string
        $field['default'] = (string)$formatter;
        $field['link'] = (string)$formatter['link'];
        $field['add-query'] = (string)$formatter['add-query'];
        $field['class'] = (string)$formatter['class'];
        $field['rel'] = (string)$formatter['rel'];
        $field['format'] = (string) $formatter['format'];
        $field['format-string'] = (string) $formatter['format-string'];
        $field['target'] = (string) $formatter['target'];
        $field['calc'] = (string) $formatter['calc'];
      }
    }

    return $field;
  }


  /**
   * Delete a node based on id
   * @param string $id
   */
  public function deleteNode($id) {
    $path = 'body//*[@id="' . $id . '"]';

    $nodes = $this->rpt_xml->xpath($path);
    if ($nodes) {
      /** @var \SimpleXMLElement $node */
      $node = $nodes[0];
      $dom=dom_import_simplexml($node);
      $dom->parentNode->removeChild($dom);
    }
  }

  /**
   * Return the xml data for the report.
   *
   * @return string
   */
  public function asXML() {
    $this->dom->formatOutput = TRUE;
    return  $this->doc_prefix . $this->dom->saveXML($this->dom->documentElement);
  }
  /**
   * Make sure all xml elements have ids
   */
  public function parse_ids() {
    $i=0;
    if ($this->rpt_xml) {
      $this->rpt_xml->registerXPathNamespace('frx', Report::FRX_NS);
      $frx_attributes = array();
      $frx_nodes = $this->rpt_xml->xpath('body//*[@frx:*]');

      if ($frx_nodes) foreach ($frx_nodes as $node) {
        $attr_nodes = $node->attributes(Report::FRX_NS);
        if ($attr_nodes) {
          // Make sure every element has an id
          $i++;
          $id = 'forena-' . $i;

          if (!isset($node['id'])) {
            $node->addAttribute('id', $id);

          }
          else {
            if (strpos((string)$node['id'], 'forena-')===0) {
              // Reset the id to the numerically generated one
              $node['id'] = $id;
            }
            else {
              // Use the id of the element
              $id = (string)$node['id'];
            }
          }

          // Save away the frx attributes in case we need them later.
          $attr_nodes = $node->attributes(Report::FRX_NS);
          $attrs = array();
          if ($attr_nodes) foreach ($attr_nodes as $key => $value) {
            $attrs[$key] = (string)$value;
          }
          // Save away the attributes
          $frx_attributes[$id] = $attrs;
        }
      }

      $this->frx_attributes = $frx_attributes;
    }
  }



  /**
   * Set the value of an element within the report
   * @param String $xpath Xpath to element being saved
   * @param string $value Value to be saved.
   */
  public function set_value($xpath, $value) {
    $xml = $this->rpt_xml;
    $i = strrpos($xpath, '/');
    $path = substr($xpath, 0, $i);
    $key = substr($xpath, $i+1);
    $nodes = $xml->xpath($path);
    if ($nodes) {
      // if the last part of the xpath is a key then assume the key
      if (strpos($key, '@')===0) {
        $key = trim($key, '@');
        if (is_null($value)) {
          unset($nodes[0][$key]);
        }
        else {
          $nodes[0][$key] = $value;
        }
      }
      // We must be refering to the text element of a node.
      else {
        if (is_null($value)) {
          unset($nodes[0]->$key);
        }
        else {
          $nodes[0]->$key = $value;
        }
      }
    }
  }

  /**
   * Default the parameters ba
   * @param array $parms Array of parameters.
   * @return boolean indicating whether the required parameters are present.
   */
  public function processParameters($parms=NULL) {
    if ($parms==NULL) {
      $parms = $this->parms;
    }
    else {
      $this->parms = $parms;
    }
    $missing_parms = FALSE;

    foreach ($this->parameterDefinitions as $key => $parm) {

      if ((@$parms[$key]==='' || @$parms[$key]===array() || @$parms[$key]===NULL) && @$parm['value']) {
        $value = $parm['value'];
        $options = array();
        if (@$parm['options']) {
          parse_str($parm['options'],$options);
        }
        switch ((string)@$parm['type']) {
          case 'date_text':
          case 'date_popup':
          case 'date_select':
             if ($value){
                $date_format = @$options['date_format'] ? $options['date_format'] : 'Y-m-d';
                $datetime = @strtotime($value);
                if ($datetime) {
                  $value = date($date_format, $datetime);
                }

              }
            break;
          default:
            if (strpos($value, '|')!==FALSE) {
              $value = explode('|', $value);
            }
        }
        $parms[$key] = $value;
      }
      //do not show report if a required parameter does not have a value
      //force the user to input a parameter

      if ((@!$parms[$key]) && (@strcmp($parm['require'], "1") == 0)) {
        $missing_parms = TRUE;
      }
    }
    $this->parms = $parms;
    return $missing_parms;
  }

  public function parametersArray() {
    $parameters = array();
    /** @var \SimpleXMLElement $head */
    $head = $this->rpt_xml->head;

    $nodes = $head->xpath('frx:parameters/frx:parm');
    if ($nodes) {
      /** @var \SimpleXMLElement $node */
      foreach ($nodes as $node) {
        $parm_def=array();
        $parm_def['default'] = (string)$node;
        foreach ($node->attributes() as $key => $value) {
          $parm_def[$key] = (string)$value;
        }
        $id = @$parm_def['id'];
        $parameters[$id] = $parm_def;
      }
    }
    return $parameters;
  }

  public function buildParametersForm() {
    $parms = $this->parameterDefinitions;
    $form =  $this->app()->buildParametersForm($parms);
    $this->getDocument()->parameters_form = $form;
  }

  public function setSort($sort, $compare_type='') {
    if (!$compare_type) {
      if (defined(SORT_NATURAL)) $compare_type = SORT_NATURAL;
    }
    else {
      if (is_string($compare_type)) {
        if (defined($compare_type)) {
          $compare_type = constant($compare_type);
        }
        else {
          $compare_type = SORT_REGULAR;
        }
      }
    }
    $this->compareType = $compare_type;
    // Assume an array of sort algorithms
    if (is_array($sort)) {
      $this->sortCriteria = $sort;
    }
    else {
      $this->sortCriteria = (array)$sort;
    }
  }


  /**
   * Comparison fucntion for user defined sorts.
   */
  public function compareFunction($a, $b) {
    $c=0;
    foreach ($this->sortCriteria as $sort) {
      //Get a value
      $this->pushData($a, '_sort');
      $va = $this->replacer->replace($sort);
      $this->popData();
      $this->pushData($b, '_sort');
      $vb = $this->replacer->replace($sort);


      switch ($this->compareType) {
      	case SORT_REGULAR:
      	  $c =  $c= $va < $vb ? -1 : ($va == $vb ? 0 : 1);
      	  break;
      	case SORT_NUMERIC:
      	  $va = floatval($va);
      	  $vb = floatval($vb);
      	  $c =  $c= $va < $vb ? -1 : ($va == $vb ? 0 : 1);
      	  break;
      	case SORT_STRING:
      	  $c = strcasecmp($va, $vb);
      	  break;
      	case SORT_NATURAL:
      	  $c = strnatcasecmp($va, $vb);
      	  break;
      	default:
      	  $c =  $c= $va < $vb ? -1 : ($va == $vb ? 0 : 1);
      }
      if ($c!==0) break;
    }
    return $c;
  }

  /**
   * Sort the current data context if it is an array.
   * @param string $sort
   * @param string $compare_type
   */
  public function sort(&$data, $sort, $compare_type='') {
    $this->setSort($sort, $compare_type);
    if (is_array($data)) {
      uasort($data, array($this, 'compareFunction'));
    }
  }

  /**
   * Iterate the data based on the provided path.
   *
   * @param string $path
   *   Xpath
   * @param string $group
   *   Grouping expression
   * @param string $sort
   *   Sort criteria expression
   * @return array
   *   grouped array of reows. 
   */
  public function group($data,  $group='', $sums= array()) {
    $rows = array();
    $totals = array();
    if (is_array($group)) $group = implode(' ', $group);
    $group = (string)$group;
    if (is_array($data) || is_object($data)) {
      foreach ($data as $row) {
        $this->pushData($row, '_group');
        $gval = $this->replacer->replace($group, TRUE);
        foreach($sums as $sum_col) {
          $sval = $this->replacer->replace($sum_col, FALSE);
          $skey = trim($sum_col, '{}');
          $totals[$gval][$skey] = isset($totals[$gval][$skey]) ? $totals[$gval][$skey] + (float)$sval : (float)$sval;
        }
        $this->popData();
        $rows[$gval][] = $row;
      }
    }
    foreach($totals as $gval => $col) {
      foreach ($col as $skey => $total) {
        $tkey = $skey . '_total';
        $rows[$gval][0]->$tkey = (string)$total;
      }
    }

    return $rows;
  }


  /**
   * Perform token replacement on a string in this report.
   * @param $value
   * @param $raw
   * @return string
   *   Token replaced value. 
   */
  public function replace($value, $raw = FALSE) {
    return $this->replacer->replace($value, $raw);
  }

  /**
   * Perform a test on a condition using token replacement enging.
   * @param $condition
   * @return bool|mixed
   */
  public function test($condition) {
    return $this->replacer->test($condition);
  }

  /**
   * Helper
   * @param $nodes
   * @param string $key_attribute
   * @param string $value_key
   * @return array
   *   key value pairs of defining attributes. 
   */
  public function extractDefinitions($nodes, $key_attribute = 'id', $value_key = '') {
    $definitions = [];
    /** @var \SimpleXMLElement $node */
    foreach($nodes as $node) {
      $id = (string)$node[$key_attribute];
      $definition = [];
      foreach ($node->attributes() as $key => $value) {
        if ($key != $key_attribute) $definition[$key] = (string)$value;
      }
      if ($value_key) $definition[$value_key] = (string)$node;
      $definitions[$id] = $definition;
    }
    return $definitions;
  }

}
