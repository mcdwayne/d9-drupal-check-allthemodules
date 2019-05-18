<?php
/**
 * @file FrxSVGGraph
 * php SVG Graph generator
 *
 * @author davidmetzler
 *
 */
namespace Drupal\forena\FrxPlugin\Renderer;
use Drupal\forena\AppService;
use Drupal\forena\FrxAPI;
use Drupal\forena\FrxPlugin\Document\DocumentInterface;
use Drupal\forena\Report;
use SVGGraph;

/**
 * SVG Graphing  Renderer
 *
 * @FrxRenderer(id = "FrxSVGGraph")
 */
class FrxSVGGraph extends RendererBase {
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

  public function __construct(Report $report, DocumentInterface $doc = NULL) {
    parent::__construct($report, $doc);
    $library = AppService::instance()->findLibrary('SVGGraph');
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
      $this->pushData($row, '_group');
      $trow = array();
      // Base group

      $gkey = $this->report->replace($group, TRUE);
      if ($this->wrap_label) $gkey = wordwrap($gkey, $this->wrap_label);
      $trow['key'] = $gkey;
      $this->popData();
      // Dimensions
      $dim_data = $dim_rows[$k];
      foreach($dim_values as $dk) {
        $dim_row = isset($dim_data[$dk]) ? $dim_data[$dk] : array();
        $this->pushData($dim_row, '_dim');
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


        $this->popData();
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


  /**
   * Render the graph.
   * @return string
   *   Rendered SVG. 
   */
  public function render() {
    // Get data from source
    $output = '';
    $this->prepareGraph();
    $type = $this->graphOptions['type'];
    if ($this->graphData && $this->validSeries()) {
      $output = $this->renderChart($type);
    }

    return $output;
  }

  static function graphTypes() {
    return array(
      'BarGraph' => array('type' => 'Bar Graph', 'style' => 'Simple' ),
      'Bar3DGraph' => array('type' => 'Bar Graph', 'style' => '3D' ),
      'StackedBarGraph' => array('type' => 'Bar Graph', 'style' => 'Stacked'),
      'GroupedBarGraph' => array('type' => 'Bar Graph', 'style' => 'Grouped'),
      'BarAndLineGraph' => array('type' => 'Bar and Line Graph', 'style' => 'Grouped'),
      'CylinderGraph' => array('type' => 'Bar Graph', 'style' => 'Cylinder'),
      'StackedCylinderGraph' => array('type' => 'Bar Graph', 'style' => 'Stacked Cylinder'),
      'GroupedCylinderGraph' =>  array('type' => 'Bar Graph', 'style' => 'Grouped Cylinder'),
      'PieGraph' => array('type' => 'Pie Chart', 'style' => 'Simple'),
      'Pie3DGraph' => array('type' => 'Pie Chart', 'style' => '3D'),
      'HorizontalBarGraph' => array('type' => 'Bar Graph', 'style' => 'Horizontal'),
      'LineGraph' =>array('type' => 'Line Graph', 'style' => 'Simple'),
      'MultiLineGraph' => array('type' => 'Line Graph', 'style' => 'Multi'),
      'ScatterGraph' => array('type' => 'Scatter Plot', 'style' => 'Simple', 'xaxis' => TRUE),
      'MultiScatterGraph' => array('type' => 'Scatter Plot', 'style' => '3D', 'xaxis' => TRUE),
      'RadarGraph' => array('type' => 'Radar Graph', 'style' => 'Simple'),
      'MultiRadarGraph' => array('type' => 'Radar Graph', 'style' => 'Multi'),
    );
  }

  static function graphOptions() {
    $data = FrxSVGGraph::graphTypes();
    foreach($data as $key => $value) {
      $type[$value['type']] = $value['type'];
      $style[$value['type']][$key] = $value['style'];
    }
    return array('types' => $type, 'styles' => $style);
  }

  function renderChart($type ) {

    $type = strtolower($type);
    // Legacy sustitions for backcward compatibility.
    if ($type == 'piechart') $type = 'piegraph';
    if ($type == 'scatterplot') $type = 'scattergraph';
    if ($type == 'multiscatterplot') $type = 'multiscattergraph';

    // Newly defined types
    $graph_types = FrxSVGGraph::graphTypes();

    // Build map for array types.
    $lower_graphs_types = array_change_key_case($graph_types);
    $graph_classes = array_combine(array_keys($lower_graphs_types), array_keys($graph_types));
    if (isset($graph_classes[$type])) {
      $class = $graph_classes[$type];
      $output = $this->renderGraph($class);
    }
    return $output;
  }


  /**
   * Checks wheter we have a valid series.
   * @return bool
   *   TRUE indicates the series if valid.
   */
  public function validSeries() {
    $valid = TRUE;
    $removed = array();
    if (isset($this->graphOptions['structure']['value'])) {
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


  function renderGraph($type) {
    // IF we don't have a library give up

    static $jsinc = '';

    $options = $this->replaceTokens($this->graphOptions);
    $data = $this->graphData;

    if (!isset($options['scatter_2d']) && ($type == 'ScatterGraph' || $type=='MultiScatterGraph') && $this->xy_data && !isset($options['scatter_2d'])) {
      $options['scatter_2d'] = TRUE;
    }
    else {
      $options['scatter_2d'] = (bool) @$options['scatter_2d'];
    }
    // Sanitize label option for SVG Graph 2.20 and greater
    if (isset($options['label']) && !is_array($options['label'])) {
      unset($options['label']);
    }
    $width = (@$options['width']) ? @$options['width'] : 600;
    $height = (@$options['height']) ? @$options['height'] : 400;

    // If the library isn't installed then quit.
    $this->graphOptions = $options;
    if (!class_exists('\SVGGraph')) return NULL; 
    $graph = new SVGGraph($width, $height, $options);
    $this->graph = $graph;
    $graph->Values($data);
    if (isset($options['colour']) && is_array($options['colour'])) {
      $graph->Colours($options['colour']);
    }
    // Generate the graph
    $output = $graph->Fetch($type, FALSE);
    // Add a viewbox to be compatible with Prince PDF generation.
    if (!@$options['noviewbox']) $options['auto_fit'] = true; 
    if (!@$options['noviewbox'] && !strpos($output, 'viewBox')) $output = str_replace('<svg width', "<svg viewBox='0 0 $width $height' width", $output);


    if (!$jsinc && $this->documentManager()->getDocumentType() == 'drupal') {
      if (@!$options['no_js']) {
        //$js =  $graph->FetchJavascript();
        //$output .= $js;
      }
      $jsinc = TRUE;
    }

    return $output;
  }
}