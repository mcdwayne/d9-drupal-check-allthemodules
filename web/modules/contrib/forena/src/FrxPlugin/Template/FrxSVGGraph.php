<?php
/**
 * @file FrxSVGGraph
 * php SVG Graph generator
 *
 * @author davidmetzler
 *
 */
namespace Drupal\forena\Template;
use Drupal\forena\FrxAPI;
use Drupal\forena\Report;
class FrxSVGGraph extends TemplateBase {
  use FrxAPI; 
  public $graph;
  public $templateName = 'Graph (svg)';
  public $xy_data = FALSE;
  public $weight;
  public $wrap_label;
  public $graphData;
  public $graphOptions;
  public $unset_attrs = array(
    'base_type',
    'crosstab_columns',
    'content',
    'sections',
    'columns',
    'style',
  );

  // Place to indicate which fields are sourced from the data.
  public $field_sources = array();

  public function __construct(Report $report) {
    parent::__construct($report);
    $library = forena_library_file('SVGGraph');
    if ($library) require_once $library;
  }

  /**
   * Re-architect the data into something that the graphing engine can work with
   */
  public function generateGraphData(&$data, $series, $key) {
    // Default controlling attributes
    $counts = array();
    $legend = array();
    $this->graphOptions['structure']['value'] = array();
    foreach($series as $col) {
      $this->graphOptions['structure']['value'][] = trim("$col", '{}');
    }


    $this->graphData = array();
    foreach ($data as $row) {
      $this->pushData($row, '_row');
      $trow = array();
      // Base group
      $trow['key'] =  $this->wrap_label ? wordwrap($this->report->replace($key, TRUE), $this->wrap_label) : $this->report->replace($key, TRUE);
      // Dimensions
      foreach($series as $col) {
          $val = $this->report->replace($col, TRUE);
          if ($val != '' && $val !==NULL) {
            $trow[trim("$col", '{}')] = $val;
            @$counts[trim("$col", '{}')]++;
          }
      }
      foreach($this->field_sources as $k => $src) {
        $trow[$k] = $this->report->replace($src, TRUE);
      }
      if(isset($this->field_sources['legend_entries'])) {
        $legend_str = $trow['legend_entries'];
        $legend[$legend_str] = $legend_str;
      }
      $this->popData();
      $this->graphData[] = $trow;
    }
    $this->counts = $counts;

    // Deal with rare case where legend are supposed to come from data
    if (isset($this->field_sources['legend_entries'])) {
      $this->graphOptions['legend_entries'] = array_values($legend);
    }
  }


   /**
   * Re-architect the data into something that the graphing engine can work with
   */
  public function generateGroupGraphData(&$block_data, $group, $series, $key, $dim, $sum) {
    $dim_headers = array();
    $dim_rows = array();
    $dim_values = array();
    $rows = array();
    $legend = array();
    $counts = array();
    $data = $this->report->group($block_data, $group, $sum);
    $this->graphOptions['structure'] = array('key' => $group);
    foreach ($data as $gk => $group_rows) {
      $row_copy = array_values($group_rows);
      $dims = $this->report->group($group_rows, $dim);
      $rows[$gk] = $group_rows[0];
      foreach($dims as $dk=>$r) {
        $dims = array_values($r);
        $dim_values[$dk] = $dk;
        $dim_rows[$gk][$dk] = $r[0];
      }
    }

    // Default controling attributes
    $dim_headers = array($key);
    $dim_columns = $series;

    foreach($dim_values as $dk) {
      foreach($dim_columns as $col) {
        $structure_idx = trim($dk, '{}') . trim($col, '{}');
        $this->graphOptions['structure']['value'][] = $structure_idx;
        foreach($this->field_sources as $k=>$fld) {
          $structure_idx = $dk . $k;
          $this->graphOptions['structure'][$k][] = $structure_idx;
        }
      }
    }

    $this->graphData = array();
    $gkey = '';
    foreach ($rows as $k=>$row) {
      $this->dmSvc->dataSvc->push($row, '_group');
      $trow = array();
      // Base group

      $gkey = $this->report->replace($group, TRUE);
      if ($this->wrap_label) $gkey = wordwrap($gkey, $this->wrap_label);
      $trow['key'] = $gkey;
      $this->dmSvc->dataSvc->pop();
      // Dimensions
      $dim_data = $dim_rows[$k];
      foreach($dim_values as $dk) {
        $dim_row = isset($dim_data[$dk]) ? $dim_data[$dk] : array();
        $this->dmSvc->dataSvc->push($dim_row, '_dim');
        foreach($dim_columns as $col) {

          $val = $this->report->replace($col, TRUE);
          if ($val !== '' && $val !== NULL) {
            $trow[trim($dk, '{}') . trim($col, '{}')] = $val;
            @$counts[trim($dk, '{}') . trim($col, '{}')]++;
          }
          foreach($this->field_sources as $fk => $src) {
            $trow[$dk . $fk] = $this->report->replace($src, TRUE);
            if (isset($this->field_sources['legend_entries'])) {
              $legend_str = $this->report->replace($this->field_sources['legend_entries']);
              $legend[$legend_str] = $legend_str;
            }

          }
        }


        $this->dmSvc->dataSvc->pop();
      }
      $this->graphData[] = $trow;
      $this->counts = $counts;

    }

    // Deal with rare case where legend are supposed to come from data
    if (isset($this->field_sources['legend_entries'])) {

      $this->graphOptions['legend_entries'] = array_values($legend);
    }
    $this->graphOptions['structure']['key'] = 'key';
    return $this->graphData;
  }

  /**
   * Add column for cross tabs.
   */
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
   * Derive config variables from graph.
   */
  public function scrapeConfig(\SimpleXMLElement $xml) {
    $this->weight = 0;

    $nodes =  $this->reportDocNode->xpath('svg');
    if ($nodes) {
      $svg = $nodes[0];
      $config = $this->mergedAttributes($svg);
    }
    $table_nodes = $this->reportDocNode->xpath('table');
    $config['gen_table'] = $table_nodes ? 1 : 0;
    // Determine graph type
    $graph_type =  isset($config['type']) ? $config['type'] : 'BarGraph';

    $types = $this->graphTypes();
    $types = array_change_key_case($types);
    $config['base_type'] = $types[strtolower($graph_type)]['type'];

    $this->extractTemplateHTML($this->reportDocDomNode, $config, array('svg', 'table'));
    if (!isset($config['key'])) $config['key'] =  @$config['label'];
    if (!isset($config['key'])) $config['key'] = @$config['seriesx'];
    $key = $config['key'];
    $dim = @$config['dim'];
    $series = @(array)$config['series'];

    if ($key) {
      $keys = explode(' ', $key);
      foreach ($keys as $k) {
        $this->addColumn('heading', $k, trim($k, '{}'), $config);
      }
    }
    if ($series) foreach ($series as $col) {
      $this->addColumn('value',$col , trim($col, '{}'), $config);
    }

    // Get the data cells
    if ($dim) {
      $dims = (array)$dim;
      foreach($dims as $dim) {
        $this->addColumn('crosstab', $dim, trim($dim, '{}'), $config);
      }
    }
    $config['style'] = $config['type'];
    return $config;
  }

  public function prepareGraph() {
    $skin_options = $this->getDataContext('skin');
    $options = isset($skin_options['FrxSVGGraph']) ? $skin_options['FrxSVGGraph'] : array();
    $attributes = $this->mergedAttributes();
    // Default in xpath for backward compatibility
    // Legacy options.  New charts should be generated using FrxAPI:attribute syntax
    if (isset($attributes['options'])) {
      $attr_options = array();
      parse_str($attributes['options'], $attr_options);
      unset($attributes['options']);
      foreach ($attr_options as $key => $value) {
        $options[$key] = $this->report->replace($value, TRUE);
      }
    }
    else {
      $options = array_merge($options, $attributes);
    }

    // Main Graphing options
    $path = $this->extract('path', $options);
    if (!$path) $path = $this->extract('xpath', $options); //Deprecated
    if (!$path) $path = '*';
    $group = $this->extract('group', $options);
    $series = @(array)$attributes['series'];
    $sums = @(array)$attributes['sum'];
    $key = @$attributes['key'];
    if (!$key) $key = @$attributes['label']; //Backward compatibility for old graphs.
    $options['key'] = $key;
    $dim = $this->extract('dim', $options);
    $this->wrap_label = (int)$this->extract('wrap_label', $options);
    if (!$key) $key = @$options['seriesx']; // Deprecated

    // Determine basic data to iterate.
    $data = $this->currentDataContext();
    if (is_object($data)) {
      if (method_exists($data, 'xpath')) {
        $nodes = $data->xpath($path);
      }
      else {
        $nodes = $data;
      }
    }
    else {
      $nodes = (array)$data;
    }

    // Force structured data
    $options['structured_data'] = TRUE;
    $options['structure'] = array('key' => 'key');

    // Default in american colour;
    $this->field_sources = array();
    if (isset($options['color'])) $options['colour'] = $options['color'];

    // Find out data that is designed to be sepecif to series.
    $this->field_sources = array();

    foreach ($options as $fk => $opt) {
      if ($fk != 'value' && $fk != 'key' && $opt && !is_array($opt) && strpos($options[$fk], '{')!==FALSE) {
        $this->field_sources[$fk] = $opt;
        $options['structure'][$fk] = $fk;
      }
    }
    if (isset($attributes['height'])) $options['height'] = $this->report->replace($attributes['height'], TRUE);
    if (isset($attributes['width'])) $options['width'] = $this->report->replace($attributes['width'], TRUE);
    if (isset($options['legend_entries']) && !isset($options['label']) && !isset($options['show_labels'])) {
      $options['show_labels'] = FALSE;
    }


    $this->graphOptions = $options;

    if ($group) {
      $this->generateGroupGraphData($nodes, $group, $series, $key, $dim, $sums);
    }
    else {
      $this->generateGraphData($nodes, $series, $key);
    }

    if (isset($this->graphOptions['legend_entries']) && !is_array($this->graphOptions['legend_entries'])) {
      $this->graphOptions['legend_entries'] = explode('|', $this->graphOptions['legend_entries']);
    }

  }

  public function validSeries() {
    $valid = TRUE;
    $removed = array();
    if (is_array($this->graphOptions['structure']['value'])) {
      foreach ($this->graphOptions['structure']['value'] as $k=>$series) {
        if (!isset($this->counts[$series])) {
          // remove empty series.
          unset($this->graphOptions['structure']['value'][$k]);
          $removed[] = $k;
        }
      }

      if ($removed) {
        $this->graphOptions['structure']['value'] = array_values($this->graphOptions['structure']['value']);
        foreach($this->graphOptions as $option => $data) {
          if (is_array($data)) {
            $modified = FALSE;
            foreach($removed as $k) {
              if (isset($data[$k])) {
                unset($this->graphOptions[$option][$k]);
                $modified = TRUE;
              }
            }
            if ($modified) {
              $this->graphOptions[$option] = array_values($this->graphOptions[$option]);
            }
          }
        }
        if (!$this->graphOptions['structure']['value']) $valid = FALSE;
      }
    }
    return $valid;
  }


  public function configForm($config) {
    $graph_options = FrxSVGGraph::graphOptions();
    $graph_types = FrxSVGGraph::graphTypes();
    $gt = array_change_key_case($graph_types);

    $type = @$config['style'];
    if (!isset($config['base_type'])) {
      $base_type = $type ? $gt[strtolower($type)]['type'] : 'Bar Graph';
    }
    else {
      $base_type = $config['base_type'];
    }
    $styles = $graph_options['styles'][$base_type];
    $form = parent::configForm($config);

    $form['base_type'] = array(
        '#type' => 'select',
        '#title' => t('Graph Type'),
        '#options' => $graph_options['types'],
        '#default_value' => $base_type,
        '#ajax' => $this->configAjax(),
    );

    $form['style'] = array(
        '#type' => 'select',
        '#title' => t('Style'),
        '#options' => $styles,
        '#default_value' => $type,
        '#ajax' => $this->configAjax(),
    );

    $form['gen_table'] = array(
        '#type' => 'checkbox',
        '#title' => t('Include data table with graph'),
        '#default_value' => @$config['gen_table'],
        '#ajax' => $this->configAjax(),
    );


    $this->weight_sort($config['crosstab_columns']);
    $types = array('heading' => t('Label'), 'crosstab' => t('Crosstab'), 'value' => 'Value', 'ignore' => t('Ignore'));
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

    $form['label'] = array(
    	'#type' => 'textfield',
      '#title' => t('Graph Label'),
      '#default_value' => @$config['label'],
    );

    $form['link'] = array(
        '#type' => 'textfield',
        '#title' => t('Link Url'),
        '#default_value' => @$config['link'],
    );

    $form['legend_entries'] = array(
        '#type' => 'textfield',
        '#title' => t('Legend'),
        '#default_value' => @$config['legend_entries'],
    );

    $form['tooltip'] = array(
        '#type' => 'textfield',
        '#title' => t('Tool Tip'),
        '#default_value' => @$config['tooltip'],
    );


    return $form;
  }

  public function configValidate(&$config) {
    $type = $config['style'];
    $base_type = $config['base_type'];
    $graph_options = $this->graphOptions();
    if(!array_key_exists($type, $graph_options['styles'][$base_type])) {
      $styles = array_keys($graph_options['styles'][$base_type]);
      $config['type'] = $config['style'] = array_shift($styles);
    }
    $config['type'] = $config['style'];
  }

  public function generate() {
    $config = $this->configuration; 
    $config['class'] = get_class($this);
    $media = 'FrxSVGGraph';
    $div = $this->blockDiv($config);
    $this->removeChildren($div);

    // PUt on the header
    if (isset($config['header']['value'])) {
      $header = $this->extract('header', $config);
      $this->addFragment($div, $header['value']);
    }

    // Determine columns and make sure we represent them all
    $found_columns = $this->columns($xml);
    if (!$found_columns) {
      $found_columns = $this->columns($xml, '/*');
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
      if ($col['type'] == 'heading') $group[] = $col['contents'];
      if ($col['type'] == 'crosstab') $dim = $col['contents'];
    }
    if ($group) $config['group'] = is_array($group) ?  implode(' ', $group) : $group;
    if ($dim) $config['dim'] = $dim;



    $this->seriesFromColumns($config);
    $this->keyFromColumns($config);

    // Clean colors
    if (isset($config['colors'])) foreach ($config['colors'] as $i => $color) if (!$color) unset($color[$i]);

    $type = $this->extract('type', $config);
    if (!$type) $type = 'Bar Graph';

    $gen_table = $this->extract('gen_table', $config);

    // LImit the config
    $frxattrs = $config;
    // Unset common option configurations?
    foreach($this->unset_attrs as $k) unset($frxattrs[$k]);

    $frxattrs = $this->arrayAttributes($frxattrs);


    $frxattrs['renderer'] = 'FrxSVGGraph';
    $frxattrs['type'] = $type;

    $this->setFirstNode($div, 2, 'svg',  NULL, NULL,  $frxattrs);

    if (isset($config['footer']['value'])) $this->addFragment($div, $config['footer']['value']);
    if ($gen_table) {
      if($group && $dim) {
        $this->generateCrossTab($xml, $config, $div, $group, $dim);
      }
      else {
        $this->generateTable($xml, $config, $div);
      }
    }

  }

  /**
   * Generate a basic table.
   * @param \SimpleXMLElement $xml
   *   Data used to preview reprot. 
   * @param array $config
   *   Template COnfiguration 
   * @param \SimpleXMLElement $div
   *   variable used to create div obejt on. 
   */
  function generateTable($xml, &$config, &$div) {
    $attrs = array('foreach' => '*');
    $table = $this->setFirstNode($div, 4, 'table');
    if (@$config['caption']) {
      $this->setFirstNode($table, 6, 'caption', $config['caption']);
    }

    $thead = $this->setFirstNode($table, 6, 'thead');
    $throw = $this->setFirstNode($thead, 8, 'tr');
    $tbody = $this->setFirstNode($table, 6, 'tbody');
    $tdrow = $this->setFirstNode($tbody, 8, 'tr', NULL, array(),$attrs);

    if (isset($config['crosstab_columns'])) foreach ($config['crosstab_columns'] as $key => $col) if ($col['type']!='ignore') {
      $this->addNode($throw, 10, 'th', $col['label']);
      $this->addNode($tdrow, 10, 'td', $col['contents']);
    }
  }

  /**
   * Generate a crosstab table.
   * @param \SimpleXMLElement $xml
   *   Preview data to generate report frx
   * @param array $config
   *   Configuration array for reprot.
   * @param \DOMNode $div
   *   Node of containing dif
   * @param string $group
   *   Token to group on 
   * @param string $dim
   *   Token to use as dimension of crosstab
   */
  function generateCrossTab($xml, &$config, &$div, $group, $dim) {
    $attrs = array();
    $table_frx['renderer'] = 'FrxCrosstab';
    $table_frx['group'] = is_array($group) ? implode(' ', $group) : $group;
    $table_frx['dim'] = $dim;
    $table = $this->setFirstNode($div, 4, 'table', NULL, $attrs, $table_frx);
    $thead = $this->setFirstNode($table, 6, 'thead');
    $throw = $this->setFirstNode($thead, 8, 'tr');
    $tbody = $this->setFirstNode($table, 6, 'tbody');
    $tdrow = $this->setFirstNode($tbody, 8, 'tr', NULL, NULL, $attrs);
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

  }

}