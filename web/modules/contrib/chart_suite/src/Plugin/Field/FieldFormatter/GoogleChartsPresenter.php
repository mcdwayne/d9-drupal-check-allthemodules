<?php

namespace Drupal\chart_suite\Plugin\Field\FieldFormatter;

// Include the structured data library.
require_once DRUPAL_ROOT . '/' .
  drupal_get_path('module', 'chart_suite') .
  '/libraries/SDSCStructuredData.1.0.1.php';

use SDSC\StructuredData\Table;
use SDSC\StructuredData\Tree;
use SDSC\StructuredData\Graph;

/**
 * Formats structured data for presentation using Google Charts.
 *
 * @ingroup chart_suite
 */
final class GoogleChartsPresenter {
  /*---------------------------------------------------------------------
   *
   * Encode methods.
   *
   *---------------------------------------------------------------------*/

  /**
   * Encodes a data object as GoogleCharts text.
   *
   * If the object is a Table or Tree, the object is encoded
   * into a JSON representation suitable for use with Google Charts.
   *
   * If the object is not a Table or Tree, an empty string is returned.
   *
   * @param mixed $item
   *   The item to encode.
   * @param string $id
   *   The CSS id, if any, for the encoded item.
   *
   * @return string
   *   The item encoded as GoogleCharts text.
   */
  public static function encode(&$item, string $id = '') {
    if ($item instanceof Table === TRUE) {
      return self::encodeTable($item, $id);
    }

    if ($item instanceof Tree === TRUE) {
      return self::encodeTree($item, $id);
    }

    if ($item instanceof Graph === TRUE) {
      return self::encodeGraph($item, $id);
    }

    return self::encodeText($item, $id);
  }

  /*---------------------------------------------------------------------
   *
   * Text methods.
   *
   *---------------------------------------------------------------------*/

  /**
   * Encodes an unknown object as empty text.
   *
   * Unfortunately, if an object is unknown, there is no way to
   * visualize it with Google Charts, so there is no meaningful
   * representation to return here.
   *
   * @param mixed $object
   *   Tthe object to encode.
   * @param string $id
   *   The CSS id, if any, for the encoded object.
   *
   * @return string
   *   Returns empty text.
   */
  public static function encodeText(&$object, string $id = '') {
    return '';
  }

  /*---------------------------------------------------------------------
   *
   * Table methods.
   *
   *---------------------------------------------------------------------*/

  /**
   * Encodes a Table object as a Google Charts JSON array.
   *
   * Chart options that can use JSON array include area, bar, column,
   * line, scatter, pie, and donut charts.
   *
   * @param \SDSC\StructuredData\Table $table
   *   The table to encode.
   *
   * @return string
   *   The table encoded as GoogleCharts text.
   */
  public static function encodeTable(Table &$table) {
    // The GoogleCharts format does not support table names,
    // table descriptions, or other table metadata.
    $nColumns = $table->getNumberOfColumns();
    $nRows = $table->getNumberOfRows();

    $text = '[';

    //
    // Column names.
    //
    // Use the best available name for the table. This could
    // be the long name, short name, or even the source file
    // name (if any).
    $text .= '[';
    for ($column = 0; $column < $nColumns; $column++) {
      // Get the best column name.
      $name = $table->getColumnBestName($column);

      $text .= "'$name'";
      if ($column !== ($nColumns - 1)) {
        $text .= ',';
      }
    }

    $text .= ']';
    if ($nRows === 0) {
      return $text;
    }

    $text .= ',';

    //
    // Encode rows.
    //
    for ($row = 0; $row < $nRows; $row++) {
      $r = $table->getRowValues($row);

      $text .= '[';

      // Add the columns for the row.
      for ($column = 0; $column < $nColumns; $column++) {
        $value = $r[$column];
        if (is_numeric($value) === TRUE) {
          $text .= "$value";
        }
        elseif (is_string($value) === TRUE) {
          $text .= "'$value'";
        }
        else {
          $text .= "'unsupported'";
        }

        if ($column !== ($nColumns - 1)) {
          $text .= ',';
        }
      }

      $text .= ']';
      if ($row !== ($nRows - 1)) {
        $text .= ',';
      }
    }

    $text .= ']';

    return $text;
  }

  /*---------------------------------------------------------------------
   *
   * Tree methods.
   *
   *---------------------------------------------------------------------*/

  /**
   * Encodes a Tree object as a Google Charts JSON array.
   *
   * @param \SDSC\StructuredData\Tree $tree
   *   The tree to encode.
   * @param string $id
   *   The CSS id, if any, for the encoded tree.
   *
   * @return string
   *   Returns he tree encoded as GoogleCharts text.
   */
  public static function encodeTree(Tree &$tree, string $id = '') {
    //
    // Encode tree.
    //
    // The result is an array of row arrays. Each row array
    // has three columns:
    // - Node name
    // - Parent node name
    // - Tool tip
    //
    // Use the best name for the node names. Use the node
    // description as the tool tip.
    $text = "[\n";
    $nodeIds = $tree->getAllNodes();
    $nNodeIds = count($nodeIds);
    for ($i = 0; $i < $nNodeIds; $i++) {
      $nodeId = $nodeIds[$i];
      $parentId = $tree->getNodeParent($nodeId);

      $nodeName = $tree->getNodeBestName($nodeId);

      if ($parentId !== (-1)) {
        $parentName = $tree->getNodeBestName($parentId);
      }
      else {
        $parentName = '';
      }

      $tooltip = $tree->getNodeDescription($nodeId);

      if ($i !== ($nNodeIds - 1)) {
        $text .= "  ['$nodeName','$parentName','$tooltip'],\n";
      }
      else {
        $text .= "  ['$nodeName','$parentName','$tooltip']\n";
      }
    }

    $text .= "]\n";

    return $text;
  }

  /*---------------------------------------------------------------------
   *
   * Graph methods.
   *
   *---------------------------------------------------------------------*/

  /**
   * Encodes a Graph object as empty text.
   *
   * Unfortunately, Google Charts does not support any graph visualization
   * techniques, so there is no meaningful way we can encode the graph
   * to return here.
   *
   * @param \SDSC\StructuredData\Graph $graph
   *   The graph to encode.
   * @param string $id
   *   The CSS id, if any, for the encoded graph.
   *
   * @return string
   *   Returns empty text.
   */
  public static function encodeGraph(Graph &$graph, string $id = '') {
    return '';
  }

}
