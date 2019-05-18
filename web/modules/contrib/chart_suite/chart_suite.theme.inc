<?php

/**
 * @file
 * Implements theme handling for the module.
 */

/**
 * Implements hook_theme().
 *
 * Responds to several module-specific named themes used to present
 * structured data content. In each case, several parameters are passed
 * to a separate template that implements the theme.
 *
 * The choice of theme is determined by the type of data being
 * presented, such as a table, tree, or graph.
 *
 * In all cases, the 'uri', 'filename', and 'filesize' variables
 * describe the original server-side file and allow the template
 * to include a download link.
 *
 * In all cases, the 'markup' variable holds server-side generated
 * JSON or HTML for the data.
 *
 * In most of the themes, the 'id' variable holds a unique ID
 * for the content. This is typically the entity ID and it enables
 * HTML tagging using the ID so that JavaScript used to present
 * the content can target a specific item on a page, even when
 * that page contains multiple files formatted by these themes.
 *
 * For specific themes, 'title' is the extracted title from the
 * data, and 'xaxis' and 'yaxis' are the extracted axis names (if any)
 * from the data. The 'description' is the file's description,
 * if any.
 */
function chart_suite_theme() {
  return [
    // Format any as HTML
    // ------------------
    // Embed pregenerated HTML generated on the server using data
    // from a table, tree, or graph. This is a fallback theme when
    // there is no clear way to present the data using a JavaScript
    // visualization package.
    'chart_suite_file_formatter_as_html' => [
      'variables'  => [
        'uri'      => NULL,
        'filename' => NULL,
        'filesize' => NULL,
        'dolink'   => NULL,
        'markup'   => NULL,
      ],
      'template'   => 'chart_suite-file-formatter-as-html',
    ],

    // Format tree as visualiation
    // ---------------------------
    // Embed JSON for tree data and invoke a JavaScript visualization
    // package to display it graphically.
    'chart_suite_file_formatter_as_tree' => [
      'variables'     => [
        'uri'         => NULL,
        'filename'    => NULL,
        'filesize'    => NULL,
        'id'          => NULL,
        'title'       => NULL,
        'dolink'      => NULL,
        'description' => NULL,
        'markup'      => NULL,
      ],
      'template'      => 'chart_suite-file-formatter-as-tree',
    ],

    // Format table as visualiation (no named rows)
    // ----------------------------
    // Embed JSON for table data and invoke a JavaScript
    // visualization package to display it graphically.
    //
    // The JSON table does not have a 1st column of strings,
    // so display choices that require named rows (e.g. a
    // pie chart) are not available.
    'chart_suite_file_formatter_as_table' => [
      'variables'     => [
        'uri'         => NULL,
        'filename'    => NULL,
        'filesize'    => NULL,
        'id'          => NULL,
        'title'       => NULL,
        'xaxis'       => NULL,
        'yaxis'       => NULL,
        'dolink'      => NULL,
        'description' => NULL,
        'markup'      => NULL,
      ],
      'template'      => 'chart_suite-file-formatter-as-table',
    ],

    // Format table as visualiation (named rows)
    // ----------------------------
    // Embed JSON for table data and invoke a JavaScript
    // visualization package to display it graphically.
    //
    // The JSON table has a 1st column of strings, so
    // include choices that use named rows (e.g. a pie
    // chart).
    'chart_suite_file_formatter_as_strtable' => [
      'variables'     => [
        'uri'         => NULL,
        'filename'    => NULL,
        'filesize'    => NULL,
        'id'          => NULL,
        'title'       => NULL,
        'xaxis'       => NULL,
        'yaxis'       => NULL,
        'dolink'      => NULL,
        'description' => NULL,
        'markup'      => NULL,
      ],
      'template'      => 'chart_suite-file-formatter-as-strtable',
    ],

    // Format any as search text
    // -------------------------
    // Show simple text for use in building the search
    // index. No HTML or scripts.
    'chart_suite_file_formatter_as_search_index' => [
      'variables'  => [
        'filename' => NULL,
        'markup'   => NULL,
      ],
      'template'   => 'chart_suite-file-formatter-as-search-index',
    ],
  ];
}
