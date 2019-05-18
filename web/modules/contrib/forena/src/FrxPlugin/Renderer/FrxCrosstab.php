<?php

namespace Drupal\forena\FrxPlugin\Renderer;
use Drupal\forena\AppService;
use Drupal\forena\FrxAPI;

/**
 * Crosstab Renderer
 *
 * @FrxRenderer(id = "FrxCrosstab")
 *
 */
class FrxCrosstab extends RendererBase {
  use FrxAPI;
  public $templateName = 'Crosstab';
  private $dim_columns = array();
  private $group_columns = array();
  private $dim_headers = array();
  private $group_headers = array();

  /**
   * Generate default headers from Embedded xml.
   */
  private function defaultHeaders() {
    $node = $this->reportNode;
    if ($node->thead && $node->thead->tr) {

      /** @var \SimpleXMLElement $cell */
      foreach ($node->thead->tr->children() as $name => $cell) {
        $hcol = array();
        $hcol['data'] = $this->innerXML($cell);
        $hcol['depth'] = 1;
        foreach ($cell->attributes() as $k => $v) {
          $hcol[$k] = (string)$v;
        }
        if ($name == 'th') {
          $this->group_headers[] = $hcol;
        }
        else {
          $this->dim_headers[] = $hcol;
        }
      }
    }
    if ($node->tbody && $node->tbody->tr) {
      foreach ($node->tbody->tr->children() as $name => $cell) {
        $col = array();
        $col['data'] = $this->innerXML($cell);
        foreach ($cell->attributes() as $k => $v) {
          $col[$k] = (string)$v;
        }
        if ($name == 'th') {
          $this->group_columns[] = $col;
        }
        else {
          $this->dim_columns[] = $col;
        }
      }
    }
  }

  /**
   * Render the crosstab
   */
  public function render() {

    $variables = $this->mergedAttributes();
    $attributes = $this->replacedAttributes();
    $format = $this->documentManager()->getDocumentType();
    if (!empty($variables['hidden']) && $format != 'csv' && $format != 'xls') {
      return '';
  }
    $path = isset($variables['path']) ? $variables['path'] : '*';
    if (!$path) $path = "*";
    $group = $variables['group'];
    $dim = $variables['dim'];
    $sum = (array)@$variables['sum'];
    // Get the current context
    $data = $this->currentDataContext();

    // Generate the data nodes.
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

    // Group the data.
    $data = $this->report->group($nodes, $group, $sum);

    $this->dim_headers = array();
    $dim_rows = array();
    $this->dim_columns = array();
    $this->group_columns = array();
    $this->group_headers = array();
    $dim_values = array();
    $rows = array();
    foreach ($data as $gk => $group_rows) {
      $dims = $this->report->group($group_rows, $dim);
      $rows[$gk] = $group_rows[0];
      foreach($dims as $dk=>$r) {
        $dim_values[$dk] = $dk;
        $dim_rows[$gk][$dk] = $r[0];
      }
    }

    // Default controling attributes
    $this->defaultHeaders();
    $hrow = array();
    foreach ($this->group_headers as $col) {
      $cell = $col;
      if (count($this->dim_columns) > 1) $cell['rowspan'] = 2;
      $hrow[] = $cell;
    }

    // Add the dimension headers.
    foreach ($dim_values as $dk) {
      foreach ($this->dim_headers as $i => $col) {
        $cell = $col;
        $cell['data'] = $dk;
        if (count($this->dim_columns) > 1) {
          $cell['data'] = $i ? $col['data'] : $dk . ' ' . $col['data'];
        }
        $hrow []  = $cell;
      }
    }

    $trows = array();
    foreach ($rows as $k=>$row) {
      $this->pushData($row, '_group');
      $trow = array();
      // Base group
      foreach($this->group_columns as $col) {
        $cell = $col;
        foreach ($col as $key => $v) {
          $cell[$key] = $this->report->replace($v);
        }
        $trow[] = $cell;
      }
      $this->popData();

      // Dimensions
      $dim_data = $dim_rows[$k];
      foreach($dim_values as $dk) {
        $dim_row = isset($dim_data[$dk]) ? $dim_data[$dk] : array();
        $this->pushData($dim_row, '_dim');
        foreach($this->dim_columns as $col) {
          $cell = $col;
          foreach ($col as $k => $v) {
            $cell[$k] = $this->report->replace($v);
          }
          $trow[] = $cell;
        }
        $this->popData();
      }
      $trows[] = $trow;

    }


    $class = 'crosstab-table';
    if (isset($attributes['class'])) $class .= ' ' . $attributes['class'];

    $elements[] = [
      '#type' => 'table',
    	'#header' => $hrow,
      '#rows' => $trows,
      '#attributes' => array('class' => array($class)),
    ];

    $output = AppService::instance()->drupalRender($elements);
    return $output;
  }


}