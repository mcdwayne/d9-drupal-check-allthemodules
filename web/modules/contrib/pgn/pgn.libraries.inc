<?php

/**
 * @file
 * Contains PGN library definitions.
 */

/**
 * Implements hook_libraries_info().
 */
function pgn_libraries_info() {
  $libraries = array();

  $libraries['pgn4web'] = array(
    'name' => 'pgn4web',
    'vendor url' => 'http://pgn4web.casaschi.net',
    'download url' => 'http://code.google.com/p/pgn4web/downloads/list',

    'version arguments' => array(
      'file' => 'pgn4web.js',
      'pattern' => "@var pgn4web_version = '([0-9\.]+)';@",
    ),
    'files' => array(
      'js' => array(
        'pgn4web.js',
      ),
    ),
  );

  $libraries['pgnviewer'] = array(
    'name' => 'LT-PGN-Viewer',
    'vendor url' => 'http://www.lutanho.net',
    'download url' => 'http://www.lutanho.net/pgn/pgnviewer.zip',

    'version arguments' => array(
      'file' => 'ltpgnviewer.html',
      'pattern' => '@<TITLE>LT-PGN-VIEWER ([0-9\.]+)</TITLE>@',
    ),
  );

  return $libraries;
}
