<?php

namespace Drupal\forena\Template;
use Drupal\forena\FrxAPI;

/**
 * Crosstab Report Template
 *
 * @FrxTemplate(id="FrxCrosstab", name="Cross Tab Data")
 *
 */
class FrxCrosstab extends TemplateBase {
  use FrxAPI; 
  public $templateName = 'Crosstab';
  private $headers = array();
  private $dim_columns = array();
  private $group_columns = array();
  private $dim_headers = array();
  private $group_headers = array();
  private $weight;


  /**
   * Crosstab configuration form.
   */
  public function configForm($config) {
    // Load header informationi from parent config.

    $form = parent::configForm($config);
    $this->weight_sort($config['crosstab_columns']);
    $types = array('heading' => t('Heading'), 'crosstab' => t('Crosstab'), 'value' => 'Value', 'ignore' => t('Ignore'));
    $form['crosstab_columns'] = array('#theme' => 'forena_element_draggable',   '#draggable_id' => 'FrxCrosstab-columns');
    foreach ($config['crosstab_columns'] as $key => $col) {
      $ctl = array();
      $ctl['label'] = array(
          '#type' => 'textfield',
          '#size' => 30,
          '#title' => t('Label'),
          '#default_value' => $col['label'],
      );

      $ctl['contents'] = array(
          '#type' => 'textfield',
          '#size' => '30',
          '#title' => t('Data'),
          '#default_value' => $col['contents'],
      );

      $ctl['type'] = array(
          '#type' => 'radios',
          '#title' => t('Type'),
          '#default_value' => $col['type'],
          '#options' => $types,
          '#ajax' => $this->configAjax()
      );

      $ctl['weight'] = array(
          "#type" => 'weight',
          '#title' => t('Weight'),
          '#delta' => 50,
          '#default_value' => $col['weight'],
      );

      $form['crosstab_columns'][$key] = $ctl;
    }
    return $form;
  }


  public function generate() {
    $config['class'] = get_class($this);
    $block = @$config['block'];
    $id = @$config['id'];
    if ($block) {
      $id = $this->idFromBlock($block);
      $config['id'] = $id . '_block';
    }
    $config['class'] = @$config['class'] ? $config['class'] . ' FrxCrosstab' : 'FrxCrosstab';
    $div = $this->blockDiv($config);

    // PUt on the header
    $this->removeChildren($div);
    if (isset($config['header']['value'])) $this->addFragment($div, $config['header']['value']);

    // Decide to inlcude columns
    $found_columns = $this->columns($xml);
    if (!$found_columns) {
      $found_columns = $this->columns($xml, '/*');
      $attrs = array();
    }

    $numeric_columns = $this->numeric_columns;
    $new_columns = @$config['crosstab_columns'] ? FALSE : TRUE;

    foreach ($found_columns as $column => $label) {
      $token = '{' . $column . '}';
      if ($new_columns) {
        $type = isset($numeric_columns[$column]) ? 'value' : 'heading';
      } else {
        $type = 'ignore';
      }
      if (!isset($config['crosstab_columns'][$column])) {
        $this->addColumn($type, '{' . $column . '}', $column, $config);
      }
    }

    // Generate the grouping row
    $group = '';
    $dim = array();
    foreach($config['crosstab_columns'] as $col) {
      if ($col['type'] == 'heading') $group .= $col['contents'];
      if ($col['type'] == 'crosstab') $dim = $col['contents'];
    }
    $r_id = $id . '-renderer';
    $table_frx['renderer'] = 'FrxCrosstab';
    $table_frx['group'] = $group;
    $table_frx['dim'] = $dim;
    $attrs[$id] = $r_id;
    //$attrs = array('foreach' => '*');
    $table = $this->setFirstNode($div, 4, 'table', NULL, $attrs, $table_frx);
    $thead = $this->setFirstNode($table, 6, 'thead');
    $throw = $this->setFirstNode($thead, 8, 'tr');
    $tbody = $this->setFirstNode($table, 6, 'tbody');
    $tdrow = $this->setFirstNode($tbody, 8, 'tr', NULL, array('id' => $id),$attrs);
    if ($config['crosstab_columns']) foreach ($config['crosstab_columns'] as $key => $col) if ($col['type']!=='ignore') {
      if ($col['type']=='heading') {
        $tag = 'th';
      }
      else {
        $tag = 'td';
      }
      if ($col['type'] != 'crosstab') {
        $this->addNode($throw, 10, $tag, $col['label']);
        $this->addNode($tdrow, 10, $tag, $col['contents']);
      }
    }
    if (isset($config['footer']['value'])) $this->addFragment($div, $config['footer']['value']);
  }

  /**
   * Default configuration validator. Simply validates header and footer attributes.
   * @param array $config
   *   configuration 
   * @return array 
   *   errors in configuration. 
   */
  public function configValidate(&$config) {
    $errors = $this->validateTextFormats($config, array('header', 'footer'));
    $dims = 0;
    if (@$config['crosstab_columns']) foreach ($config['crosstab_columns'] as $col) {
      if (@$col['type']=='value') {
        $dims++;
      }
    }
    if ($dims > 1)  $errors[] = t('Too many value columns.  Please select only one');

    return $errors;
  }

  private function addColumn($type, $token, $label,  &$config) {
    $key = trim($token, '{}');
    $this->weight++;
    $config['crosstab_columns'][$key] = array(
        'contents' => $token,
        'label' => $label,
        'type' => $type,
        'weight' => $this->weight,
    );
  }

  /**
   * Extract table configuration from the HTML
   * @see FrxRenderer::scrapeConfig()
   */
  public function scrapeConfig(\SimpleXMLElement $xml) {
    $this->weight = 0;
    $config=array();
    $nodes =  $this->reportDocNode->xpath('//table');
    if ($nodes) {
      $table = $nodes[0];
      $attrs = $this->mergedAttributes($table);
    }
    $config['group'] = $group = $attrs['group'];
    $config['dim']  = $dim = $attrs['dim'];
    $this->extractTemplateHTML($this->reportDocDomNode, $config, array('table'));
    $head_ths = $this->extractXPathInnerHTML('*//thead/tr/th', $this->reportDocDomNode, FALSE);
    $head_tds = $this->extractXPathInnerHTML('*//thead/tr/td', $this->reportDocDomNode, FALSE);
    $body_ths = $this->extractXPathInnerHTML('*//tbody/tr/th', $this->reportDocDomNode, FALSE);
    $body_tds = $this->extractXPathInnerHTML('*//tbody/tr/td', $this->reportDocDomNode, FALSE);
    $heading_cols = array_combine($head_ths, $body_ths);
    $data_cols = array_combine($head_tds, $body_tds);
    // Get the named headers
    foreach($heading_cols as $label=>$token) {
      $this->addColumn('heading', $token, $label, $config);
    }
    // Get the data cells
    if ($dim) {
      $dims = (array)$dim;
      foreach($dims as $dim) {
        $this->addColumn('crosstab', $dim, trim($dim, '{}'), $config);
      }
    }
    foreach($data_cols as $label=>$token) {
      $this->addColumn('value', $token, $label, $config);
    }
    return $config;
  }
}