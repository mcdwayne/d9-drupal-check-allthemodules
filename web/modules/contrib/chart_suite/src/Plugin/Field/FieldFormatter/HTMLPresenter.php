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
 * Presents structured data in HTML.
 *
 * A Table object is presented as HTML tables with rows and columns
 * of values from the table.
 *
 * A Tree object is presented as an HTML nested ordered list.
 *
 * A Graph object is presented as an HTML table listing nodes and edges.
 *
 * @ingroup chart_suite
 */
final class HTMLPresenter {
  /*---------------------------------------------------------------------
   *
   * Constants.
   *
   *---------------------------------------------------------------------*/

  // Text CSS class names.
  const TEXT_WRAPPER_CLASS = "structured-data-text-wrapper";

  // Table CSS class names.
  const TABLE_WRAPPER_CLASS     = "structured-data-table-wrapper";
  const TABLE_CLASS             = "structured-data-table";
  const TABLE_CAPTION_CLASS     = "structured-data-table-caption";
  const TABLE_DESCRIPTION_CLASS = "structured-data-table-description";

  const TABLE_EVEN_COLUMN_CLASS = "structured-data-table-even-column";
  const TABLE_ODD_COLUMN_CLASS = "structured-data-table-odd-column";

  const TABLE_EVEN_ROW_CLASS = "structured-data-table-even-row";
  const TABLE_ODD_ROW_CLASS = "structured-data-table-odd-row";

  // Tree CSS class names.
  const TREE_WRAPPER_CLASS        = "structured-data-tree-wrapper";
  const TREE_CLASS                = "structured-data-tree";
  const TREE_CAPTION_CLASS        = "structured-data-tree-caption";
  const TREE_DESCRIPTION_CLASS    = "structured-data-tree-description";
  const TREE_NODE_CLASS           = "structured-data-tree-node";
  const TREE_LIST_CLASS           = "structured-data-tree-list";
  const TREE_NODE_ATT_TABLE_CLASS = "structured-data-tree-node-att-table";
  const TREE_NODE_ATT_NAME_CLASS  = "structured-data-tree-node-att-name";
  const TREE_NODE_ATT_VALUE_CLASS = "structured-data-tree-node-att-value";

  // Graph CSS class names.
  const GRAPH_WRAPPER_CLASS     = "structured-data-graph-wrapper";
  const GRAPH_CLASS             = "structured-data-graph";
  const GRAPH_CAPTION_CLASS     = "structured-data-graph-caption";
  const GRAPH_DESCRIPTION_CLASS = "structured-data-graph-description";

  const GRAPH_NODE_SOURCE_COLUMN_CLASS      = "structured-data-graph-node-source";
  const GRAPH_NODE_DESTINATION_COLUMN_CLASS = "structured-data-graph-node-destination";
  const GRAPH_EDGE_COLUMN_CLASS             = "structured-data-graph-edge";
  const GRAPH_EVEN_COLUMN_CLASS             = "structured-data-graph-even-column";
  const GRAPH_ODD_COLUMN_CLASS              = "structured-data-graph-odd-column";

  const GRAPH_EVEN_ROW_CLASS       = "structured-data-graph-even-row";
  const GRAPH_ODD_ROW_CLASS        = "structured-data-graph-odd-row";
  const GRAPH_NODE_ATT_TABLE_CLASS = "structured-data-graph-node-att-table";
  const GRAPH_NODE_ATT_NAME_CLASS  = "structured-data-graph-node-att-name";
  const GRAPH_NODE_ATT_VALUE_CLASS = "structured-data-graph-node-att-value";
  const GRAPH_EDGE_ATT_TABLE_CLASS = "structured-data-graph-edge-att-table";
  const GRAPH_EDGE_ATT_NAME_CLASS  = "structured-data-graph-edge-att-name";
  const GRAPH_EDGE_ATT_VALUE_CLASS = "structured-data-graph-edge-att-value";

  // Graph table column names.
  const GRAPH_NODE_SOURCE_COLUMN_NAME = "Source node";
  const GRAPH_NODE_DESTINATION_COLUMN_NAME = "Destination node";
  const GRAPH_EDGE_COLUMN_NAME = "Edge";

  /*---------------------------------------------------------------------
   *
   * Utilities.
   *
   *---------------------------------------------------------------------*/

  /**
   * Converts the given item to a string.
   *
   * If the item is a scalar, it is converted to a default string
   * representation and returned.
   *
   * If the item is an object, and the object has a standard __toString
   * method, that method is invoked and the results returned.
   *
   * Otherwise the item is an array or object and we have no clear way
   * to convert it to a human-readable string. Use var_export to create a
   * raw dump.  Since we are using the output for HTML, add <pre>...</pre>
   * around the dump.
   *
   * @param mixed $item
   *   The item to convert to a string.
   */
  private static function convertToString(&$item) {
    if ($item === NULL) {
      return 'null';
    }

    if (is_scalar($item) === TRUE) {
      return strval($item);
    }

    if (is_object($item) === TRUE &&
        method_exists($item, "__toString") === TRUE) {
      return strval($item);
    }

    return '<pre>' . var_export($item, TRUE) . '</pre>';
  }

  /*---------------------------------------------------------------------
   *
   * Encode methods.
   *
   *---------------------------------------------------------------------*/

  /**
   * Encodes a data object as HTML text.
   *
   * If the object is a Table, Tree, or Graph, the object is encoded
   * into a readable representation.  Tables are shown as HTML tables
   * with rows and columns of values. Graphs are shown as HTML tables
   * listing the rows and edges, and their values. Trees are shown as
   * HTML nested lists, starting with the root, and each node has its
   * values shown.
   *
   * If the object is not a Table, Tree, or Graph, the object is encoded
   * as text. Scalars and objects with __toString implemented are converted
   * to strings and shown within an HTML div. Other objects are shown
   * as a raw text dump within HTML pre and div tags.
   *
   * @param mixed $item
   *   The item to encode.
   * @param string $id
   *   The CSS id, if any, for the encoded item.
   *
   * @return string
   *   Returns the item encoded as HTML text.
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
   * Encodes an unknown object as HTML text.
   *
   * The object is converted to a string, then shown as-is.  This is
   * a fall-back when an object's type is not recognized.
   *
   * When an object is a scalar (e.g. integer, string, etc.), it is
   * converted to a string and wrapped in a "div".
   *
   * When an object has the standard __toString method, it is used to
   * convert to a human-readable string and wrapped in a "div".
   *
   * All other objects are dumped using var_export. That text is wrapped
   * in a "pre" and a "div".
   *
   * @code
   *  <div id="ID" class="structured-data-text-wrapper">
   *  TEXT
   *  </div>
   * @endcode
   *
   * @param mixed $object
   *   The object to encode.
   * @param string $id
   *   The CSS id, if any, for the encoded object.
   *
   * @return string
   *   Returns the object encoded as HTML text.
   */
  public static function encodeText(&$object, string $id = '') {
    //
    // Encode it.
    //
    $text = '';

    $cls = self::TEXT_WRAPPER_CLASS;
    $text .= "<div ";
    if (empty($id) === FALSE) {
      $text .= "id=\"$id\" ";
    }

    $text .= "class=\"$cls\">\n";
    $text .= self::convertToString($object);
    $text .= "</div>\n";

    return $text;
  }

  /*---------------------------------------------------------------------
   *
   * Table methods.
   *
   *---------------------------------------------------------------------*/

  /**
   * Encodes a Table object as HTML text.
   *
   * The Table object is encoded as an HTML table with the given
   * optional CSS ID. CSS classes are assigned to the table, caption,
   * column headings, columns, and rows.
   *
   * @code
   *  <div id="ID" class="structured-data-table-wrapper">
   *  <table class="structured-data-table">
   *    <caption class="structured-data-table-caption">TABLENAME</caption>
   *    <thead>
   *      <tr>
   *        <th class="structured-data-table-odd-column">COLUMNAME</th>
   *        ...
   *      </tr>
   *    </thead>
   *    <tbody>
   *      <tr class="structured-data-table-odd-row">
   *        <td class="structured-data-table-odd-column">DATA</td>
   *        ...
   *      </tr>
   *      ...
   *    </tbody>
   *  </table>
   *  <div class="structured-data-table-description>DESCRIPTION</div>
   *  </div>
   * @endcode
   *
   * @param \SDSC\StructuredData\Table $table
   *   The table to encode.
   * @param string $id
   *   The CSS id, if any, for the encoded table.
   *
   * @return string
   *   The table encoded as HTML text.
   */
  public static function encodeTable(Table &$table, string $id = '') {
    //
    // Encode table start.
    //
    $text = '';

    $cls = self::TABLE_WRAPPER_CLASS;
    $text .= "<div ";
    if (empty($id) === FALSE) {
      $text .= "id=\"$id\" ";
    }

    $text .= "class=\"$cls\">\n";

    $cls = self::TABLE_CLASS;
    $text .= "  <table class=\"$cls\">\n";

    //
    // Encode caption.
    //
    // Use the best available name for the table. This could
    // be the long name, short name, or even the source file
    // name (if any).
    $tableName = $table->getBestName();
    if (empty($tableName) === FALSE) {
      $cls = self::TABLE_CAPTION_CLASS;
      $text .= "    <caption class=\"$cls\">$tableName</caption>\n";
    }

    //
    // Encode header.
    //
    // Generate a single row with column names.
    //
    // Use the best available column name. This could be the
    // long name, short name, or column number.
    $text .= "    <thead>\n";
    $text .= "      <tr>\n";
    $nColumns = $table->getNumberOfColumns();
    for ($column = 0; $column < $nColumns; $column++) {
      // Get the best name.
      $name = $table->getColumnBestName($column);

      // Get the even/odd class name.
      $cls = (($column % 2) === 0) ?
        self::TABLE_ODD_COLUMN_CLASS : self::TABLE_EVEN_COLUMN_CLASS;

      $text .= "        <th class=\"$cls\">$name</th>\n";
    }

    $text .= "      </tr>\n";
    $text .= "    </thead>\n";

    //
    // Encode rows.
    //
    $text .= "    <tbody>\n";
    $nRows = $table->getNumberOfRows();
    for ($row = 0; $row < $nRows; $row++) {
      $r = $table->getRowValues($row);

      // Get the even/odd class name.
      $cls = (($row % 2) === 0) ?
          self::TABLE_ODD_ROW_CLASS : self::TABLE_EVEN_ROW_CLASS;

      $text .= "      <tr class=\"$cls\">\n";

      // Add the columns for the row.
      for ($column = 0; $column < $nColumns; $column++) {
        // Get the even/odd class name.
        $cls = (($column % 2) === 0) ?
            self::TABLE_ODD_COLUMN_CLASS : self::TABLE_EVEN_COLUMN_CLASS;

        $value = $r[$column];
        $s = self::convertToString($value);

        $text .= "        <td class=\"$cls\">$s</td>\n";
      }

      $text .= "      </tr>\n";
    }

    $text .= "    </tbody>\n";

    // Close table.
    $text .= "  </table>\n";

    // Description.
    $description = $table->getDescription();
    if (empty($description) === FALSE) {
      $cls = self::TABLE_DESCRIPTION_CLASS;
      $text .= "  <div class=\"$cls\">$description</div>\n";
    }

    $text .= "</div>\n";

    return $text;
  }

  /*---------------------------------------------------------------------
   *
   * Tree methods.
   *
   *---------------------------------------------------------------------*/

  /**
   * Encodes a Tree object as HTML text.
   *
   * The Tree object is encoded as an HTML list wrapped with a div
   * with the given optional CSS ID. CSS classes are assigned to the
   * list items.
   *
   * @code
   *  <div id="ID" class="structured-data-tree-wrapper">
   *    <p class="structured-data-tree-caption">TREENAME</p>
   *    <div id="ID" class="structured-data-tree">
   *      <ul>
   *        <li class="structured-data-tree-node">NODENAME
   *        <ul>
   *          <li class="structured-data-tree-node">NODENAME</li>
   *          ...
   *        </ul>
   *        </li>
   *      </ul>
   *    </div>
   *    <div class="structured-data-tree-description>DESCRIPTION</div>
   *  </div>
   * @endcode
   *
   * @param \SDSC\StructuredData\Tree $tree
   *   The tree to encode.
   * @param string $id
   *   The CSS id, if any, for the encoded tree.
   *
   * @return string
   *   Returns the tree encoded as HTML text.
   */
  public static function encodeTree(Tree &$tree, string $id = '') {
    //
    // Encode tree wrapper and caption.
    //
    // Use the best available name for the tree. This could
    // be the long name, short name, or even the source file
    // name (if any).
    $text = '';

    // Div wrapper.
    $cls = self::TREE_WRAPPER_CLASS;
    $text .= "<div ";
    if (empty($id) === FALSE) {
      $text .= "id=\"$id\" ";
    }

    $text .= "class=\"$cls\">\n";

    // Tree caption.
    $treeName = $tree->getBestName();
    if (empty($treeName) === FALSE) {
      $cls = self::TREE_CAPTION_CLASS;
      $text .= "  <p class=\"$cls\">$treeName</p>\n";
    }

    $cls = self::TREE_CLASS;
    $text .= "  <div class=\"$cls\">\n";

    //
    // Encode tree nodes.
    //
    $rootId = $tree->getRootNodeID();
    if ($rootId !== (-1)) {
      $cls   = self::TREE_LIST_CLASS;
      $text .= "    <ul class=\"$cls\">\n";
      $text .= self::recursivelyEncodeTree($tree, '    ', $rootId);
      $text .= "    </ul>\n";
    }

    // Close div.
    $text .= "  </div>\n";

    // Description.
    $description = $tree->getDescription();
    if (empty($description) === FALSE) {
      $cls = self::TREE_DESCRIPTION_CLASS;
      $text .= "  <div class=\"$cls\">$description</div>\n";
    }

    $text .= "</div>\n";

    return $text;
  }

  /**
   * Encodes a tree recursively.
   *
   * Recursively encodes the given tree, starting at the selected node,
   * and indenting each line with the given string.
   *
   * @param \SDSC\StructuredData\Tree $tree
   *   The tree object to be encoded.
   * @param string $indent
   *   The text string to prepend to every line of encoded text.
   * @param int $nodeId
   *   The unique positive numeric ID of the tree node to encode, along
   *   with all of its children.
   */
  private static function recursivelyEncodeTree(
    Tree &$tree,
    string $indent,
    int $nodeId) {
    // Use the best available node name. This could be the
    // long name, short name, or column number.
    $name = $tree->getNodeBestName($nodeId);

    // List item.
    $cls = self::TREE_NODE_CLASS;
    $text = "$indent<li class=\"$cls\">$name\n";

    // Attributes, if any.
    $text .= self::encodeTreeNodeAttributes($tree, $nodeId);

    // Children.
    $cls = self::TREE_LIST_CLASS;
    $children = $tree->getNodeChildren($nodeId);
    if (empty($children) === FALSE) {
      $indent2 = $indent . '  ';
      $text .= "$indent<ul class=\"$cls\">\n";
      for ($i = 0; $i < count($children); $i++) {
        $text .= self::recursivelyEncodeTree($tree, $indent2, $children[$i]);
      }

      $text .= "$indent</ul>\n";
    }

    // List item close.
    $text .= "$indent</li>\n";

    return $text;
  }

  /**
   * Encodes tree node attributes.
   *
   * Looks up the attributes for the selected node, strips out well-known
   * attributes, and formats the remainder as a table.
   *
   * @param \SDSC\StructuredData\Tree $tree
   *   The tree to encode.
   * @param int $nodeId
   *   The node id.
   *
   * @return string
   *   Returns the encoded attributes.
   */
  private static function encodeTreeNodeAttributes(
    Tree &$tree,
    int $nodeId) {
    // Get the node's attributes, if any.
    $att = $tree->getNodeAttributes($nodeId);

    // The attributes include well-known attributes, like the node
    // name, plus any special attributes, such as node values.
    // Strip away the well-known attributes and return the rest.
    foreach (Tree::$WELL_KNOWN_NODE_ATTRIBUTES as $name => $flag) {
      unset($att[$name]);
    }

    // If there's nothing left, there's nothing to encode.
    if (empty($att) === TRUE) {
      return '';
    }

    $text = '';

    $cls = self::TREE_NODE_ATT_TABLE_CLASS;
    $clsn = self::TREE_NODE_ATT_NAME_CLASS;
    $clsv = self::TREE_NODE_ATT_VALUE_CLASS;

    $text .= "<table class=\"$cls\">\n";
    $text .= "  <tbody>\n";
    foreach ($att as $name => $value) {
      $s = self::convertToString($value);
      $text .= "    <tr>";
      $text .= "<td class=\"$clsn\">$name</td>";
      $text .= "<td class=\"$clsv\">$s</td>";
      $text .= "</tr>\n";
    }

    $text .= "  </tbody>\n";
    $text .= "</table>\n";

    return $text;
  }

  /*---------------------------------------------------------------------
   *
   * Graph methods.
   *
   *---------------------------------------------------------------------*/

  /**
   * Encodes a Graph object as HTML text.
   *
   * There is no slick diagramatic way to encode a graph within the
   * 2D list-focused style of HTML page text.  Instead, we simply
   * produce a table listing all of the graph nodes and outgoing
   * edges.
   *
   * Structurally, this looks like:
   *
   * @code
   *  | Node     | Edge  | Edge destination |
   *  | ...      | ...   | ...              |
   * @endcode
   *
   * @code
   *  <div id="ID" class="structured-data-graph-wrapper">
   *    <table id="ID" class="structured-data-table">
   *      <caption class="structured-data-table-caption">TABLENAME</caption>
   *      <thead>
   *        <tr>
   *          <th class="structured-data-table-odd-column">COLUMNAME</th>
   *          ...
   *        </tr>
   *      </thead>
   *      <tbody>
   *        <tr class="structured-data-table-odd-row">
   *          <td class="structured-data-table-odd-column">DATA</td>
   *          ...
   *        </tr>
   *        ...
   *      </tbody>
   *    </table>
   *    <div class="structured-data-graph-description>DESCRIPTION</div>
   *  </div>
   * @endcode
   *
   * @param \SDSC\StructuredData\Graph $graph
   *   The graph to encode.
   * @param string $id
   *   The CSS id, if any, for the encoded graph.
   *
   * @return string
   *   Returns the graph encoded as HTML text.
   */
  public static function encodeGraph(Graph &$graph, string $id = '') {
    //
    // Encode graph start.
    //
    $text = '';

    $cls = self::GRAPH_WRAPPER_CLASS;
    $text .= "<div ";
    if (empty($id) === FALSE) {
      $text .= "id=\"$id\" ";
    }

    $text .= "class=\"$cls\">\n";

    $cls = self::GRAPH_CLASS;
    $text .= "  <table class=\"$cls\">\n";

    //
    // Encode caption.
    //
    // Use the best available name for the graph. This could
    // be the long name, short name, or even the source file
    // name (if any).
    $graphName = $graph->getBestName();
    if (empty($graphName) === FALSE) {
      $cls   = self::GRAPH_CAPTION_CLASS;
      $text .= "    <caption class=\"$cls\">$graphName</caption>\n";
    }

    //
    // Encode header.
    //
    // Generate a single row with fixed column names.
    $text .= "    <thead>\n";
    $text .= "      <tr>\n";

    $text .= "        <th class=\"" .
        self::GRAPH_ODD_COLUMN_CLASS . "," .
        self::GRAPH_NODE_SOURCE_COLUMN_CLASS . "\">" .
        self::GRAPH_NODE_SOURCE_COLUMN_NAME . "</th>\n";
    $text .= "        <th class=\"" .
        self::GRAPH_EVEN_COLUMN_CLASS . "," .
        self::GRAPH_EDGE_COLUMN_CLASS . "\">" .
        self::GRAPH_EDGE_COLUMN_NAME . "</th>\n";
    $text .= "        <th class=\"" .
        self::GRAPH_ODD_COLUMN_CLASS . "," .
        self::GRAPH_NODE_DESTINATION_COLUMN_CLASS . "\">" .
        self::GRAPH_NODE_DESTINATION_COLUMN_NAME . "</th>\n";

    $text .= "      </tr>\n";
    $text .= "    </thead>\n";

    //
    // Encode nodes.
    //
    $text .= "    <tbody>\n";
    $n = 0;
    foreach ($graph->getAllNodes() as $nodeId) {
      // Use the best available node name. This could be the
      // long name, short name, or column number.
      $srcName = $graph->getNodeBestName($nodeId);
      $firstEdge = TRUE;

      foreach ($graph->getNodeEdges($nodeId) as $edgeId) {
        $cls = (($n % 2) === 0) ?
            self::GRAPH_ODD_ROW_CLASS : self::GRAPH_EVEN_ROW_CLASS;
        $text .= "      <tr class=\"$cls\">\n";

        // Source node's name.  Only shown on first edge
        // for the node.
        $text .= "        <td class=\"" .
            self::GRAPH_ODD_COLUMN_CLASS . "," .
            self::GRAPH_NODE_SOURCE_COLUMN_CLASS . "\">";
        if ($firstEdge === TRUE) {
          $text .= $srcName;
          $firstEdge = FALSE;

          // Add node attributes only when the name is given.
          $text .= self::encodeGraphNodeAttributes($graph, $nodeId);
        }

        $text .= "</td>\n";

        // Use the best available edge name. This could be the
        // long name, short name, or column number.
        $edgeName = $graph->getEdgeBestName($edgeId);
        $text .= "        <td class=\"" .
          self::GRAPH_EVEN_COLUMN_CLASS . "," .
          self::GRAPH_EDGE_COLUMN_CLASS . "\">$edgeName";

        // Edge's attributes.
        $text .= self::encodeGraphEdgeAttributes($graph, $edgeId);
        $text .= "        </td>\n";

        // Destination node's name.
        $edgeNodes = $graph->getEdgeNodes($edgeId);
        if ($edgeNodes[0] === $nodeId) {
          $destId = $edgeNodes[1];
        }
        else {
          $destId = $edgeNodes[0];
        }

        $destName = $graph->getNodeBestName($destId);
        $text .= "        <td class=\"" .
            self::GRAPH_ODD_COLUMN_CLASS . "," .
            self::GRAPH_NODE_DESTINATION_COLUMN_CLASS .
            "\">$destName\n";

        // Add node attributes.
        $text .= self::encodeGraphNodeAttributes($graph, $destId);
        $text .= "        </td>\n";
      }

      $n++;
    }

    $text .= "    </tbody>\n";

    // Close graph.
    $text .= "  </table>\n";

    // Description.
    $description = $graph->getDescription();
    if (empty($description) === FALSE) {
      $cls = self::GRAPH_DESCRIPTION_CLASS;
      $text .= "  <div class=\"$cls\">$description</div>\n";
    }

    $text .= "</div>\n";

    return $text;
  }

  /**
   * Encodes graph node attributes.
   *
   * Looks up the attributes for the selected node, strips out well-known
   * attributes, and formats the remainder as a table.
   *
   * @param \SDSC\StructuredData\Graph $graph
   *   The graph to encode.
   * @param int $nodeId
   *   The node id.
   *
   * @return string
   *   Returns the encoded attributes.
   */
  private static function encodeGraphNodeAttributes(
    Graph &$graph,
    int $nodeId) {
    // Get the node's attributes, if any.
    $att = $graph->getNodeAttributes($nodeId);

    // The attributes include well-known attributes, like the node
    // name, plus any special attributes, such as node values.
    // Strip away the well-known attributes and return the rest.
    foreach (Graph::$WELL_KNOWN_NODE_ATTRIBUTES as $name => $flag) {
      unset($att[$name]);
    }

    // If there's nothing left, there's nothing to encode.
    if (empty($att) === TRUE) {
      return '';
    }

    $text = '';

    $cls = self::GRAPH_NODE_ATT_TABLE_CLASS;
    $clsn = self::GRAPH_NODE_ATT_NAME_CLASS;
    $clsv = self::GRAPH_NODE_ATT_VALUE_CLASS;

    $text .= "          <table class=\"$cls\">\n";
    $text .= "            <tbody>\n";
    foreach ($att as $name => $value) {
      $s = self::convertToString($value);
      $text .= "             <tr>";
      $text .= "<td class=\"$clsn\">$name</td>";
      $text .= "<td class=\"$clsv\">$s</td>";
      $text .= "</tr>\n";
    }

    $text .= "            </tbody>\n";
    $text .= "          </table>\n";

    return $text;
  }

  /**
   * Encodes graph edge attributes.
   *
   * Looks up the attributes for the selected edge, strips out well-known
   * attributes, and formats the remainder as a table.
   *
   * @param \SDSC\StructuredData\Graph $graph
   *   The graph to encode.
   * @param int $edgeId
   *   The edge id.
   *
   * @return string
   *   The encoded attributes.
   */
  private static function encodeGraphEdgeAttributes(
    Graph &$graph,
    int $edgeId) {
    // Get the edge's attributes, if any.
    $att = $graph->getEdgeAttributes($edgeId);

    // The attributes include well-known attributes, like the edge
    // name, plus any special attributes, such as edge values.
    // Strip away the well-known attributes and return the rest.
    foreach (Graph::$WELL_KNOWN_EDGE_ATTRIBUTES as $name => $flag) {
      unset($att[$name]);
    }

    // If there's nothing left, there's nothing to encode.
    if (empty($att) === TRUE) {
      return '';
    }

    $text = '';

    $cls = self::GRAPH_EDGE_ATT_TABLE_CLASS;
    $clsn = self::GRAPH_EDGE_ATT_NAME_CLASS;
    $clsv = self::GRAPH_EDGE_ATT_VALUE_CLASS;

    $text .= "          <table class=\"$cls\">\n";
    $text .= "            <tbody>\n";
    foreach ($att as $name => $value) {
      $s = self::convertToString($value);
      $text .= "             <tr>";
      $text .= "<td class=\"$clsn\">$name</td>";
      $text .= "<td class=\"$clsv\">$s</td>";
      $text .= "</tr>\n";
    }

    $text .= "            </tbody>\n";
    $text .= "          </table>\n";

    return $text;
  }

}
