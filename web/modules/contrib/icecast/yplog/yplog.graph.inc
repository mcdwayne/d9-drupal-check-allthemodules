<?php

/**
 * Generates an index of available logs, limited to the past week.
 */
function yplog_graph_index() {
  $rows = array();
  $max_touches = 0;
  $header = array(
    array('data' => t('URL'), 'field' => 'listen_url'),
    array('data' => t('Max listeners'), 'field' => 'maximum'),
    array('data' => t('Average listeners'), 'field' => 'average'),
    array('data' => t('Relative uptime'), 'field' => 'touches'),
  );
  $query = db_select('yp_log');
  $query->addField('yp_log', 'listen_url');
  $query->addExpression('MAX(listeners)', 'maximum');
  $query->addExpression('ROUND(AVG(listeners))', 'average');
  $query->addExpression('COUNT(*)', 'touches');
  $query->condition('timestamp', REQUEST_TIME - 604800, '>');
  $query->groupBy('listen_url');
  $query = $query
    ->extend('TableSort')
    ->orderByHeader($header);
  $result = $query->execute();
  foreach ($result as $stream) {
    $link = 'yp/log/' . str_replace('http://', '', $stream->listen_url);
    // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $rows[] = array(
//       l($stream->listen_url, $link),
//       $stream->maximum,
//       $stream->average,
//       $stream->touches,
//     );

    $max_touches = max($max_touches, $stream->touches);
  }
  foreach ($rows as $key => $row) {
    $rows[$key][3] = round(100 * ($rows[$key][3] / $max_touches), 2) . '%';
  }
  if (empty($rows)) {
    $rows[] = array(array('data' => t('No stream data found.'), 'colspan' => 4));
  }
  // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// return(
//     theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array(), 'caption' => t('Listing all streams seen in the past seven days.')))
//     . t('Uptime is relative to the stream with the highest uptime as seen by this YP server. Therefore "100%" does not necessarily indicate the stream was up 100% of the time. The log takes only periodic snapshots of directory data; therefore it may have missed the actual maximum listenership of a stream.')
//   );

}

/**
 * Page callback for YP log graph.
 */
function yplog_graph_page($host, $path = '') {
  $listen_url = 'http://' . $host . '/' . $path;
  // @FIXME
// drupal_set_title() has been removed. There are now a few ways to set the title
// dynamically, depending on the situation.
// 
// 
// @see https://www.drupal.org/node/2067859
// drupal_set_title($listen_url);

  // @FIXME
// l() expects a Url object, created from a route name or external URI.
// drupal_set_breadcrumb(array(l(t('Home'), NULL), l(t('Stream directory'), 'yp'), l(t('Reports'), 'yp/log')));

  $directory = 'public://yplog';
  file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
  $filename = $directory . '/' . md5($listen_url) . '.png';
  // If image is stale or not found, generate new image.
  if (!file_exists($filename) || REQUEST_TIME - filemtime($filename) > 900) {
    yplog_graph($listen_url, $filename);
  }
  // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// return theme('yplog_graph', array('listen_url' => $listen_url, 'filename' => $filename));

}

/**
 * Generates graph image.
 */
function yplog_graph($listen_url, $filename) {
  $result = db_query('SELECT timestamp, listeners, listen_url FROM {yp_log} WHERE listen_url = :listen_url AND timestamp > :timestamp', array(':listen_url' => $listen_url, ':timestamp' => REQUEST_TIME - 604800));
  require_once('Image/Graph.php');
  $Dataset =& Image_Graph::factory('dataset');
  $max_listeners = 0;
  foreach ($result as $log) {
    $Dataset->addPoint($log->timestamp, $log->listeners);
    $max_listeners = max($log->listeners, $max_listeners);
  }
  if (!$Dataset->count()) {
    return MENU_NOT_FOUND;
  }
  $Dataset->setName(t('Date (@timezone)', array('@timezone' => yplog_graph_timezone())));
  require_once('Image/Canvas.php');
  $Canvas =& Image_Canvas::factory('png', array('width' => 640, 'height' => 320, 'antialias' => 'native'));
  $Graph =& Image_Graph::factory('graph', $Canvas); 
  $Font =& $Graph->addNew('font', \Drupal::config('yplog.settings')->get('yplog_font'));
  $Font->setSize(8);
  $Graph->setFont($Font);
  $Graph->add(
    Image_Graph::vertical(
      Image_Graph::factory('title', array($listen_url, 11)),
      $Plotarea = Image_Graph::factory('plotarea', array('Image_Graph_Axis')),
    5)
  ); 
  $Grid =& $Plotarea->addNew('line_grid', array(), IMAGE_GRAPH_AXIS_Y);
  $Grid->setLineColor('white@0.4');
  $Plot =& $Plotarea->addNew('Image_Graph_Plot_Impulse', array(&$Dataset));
  $Plot->setLineColor('darkgreen@0.4');
  $Plotarea->setFillColor('yellow@0.1');
  $AxisX =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
  $AxisX->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Function', 'yplog_graph_date'));
  $AxisX->forceMinimum(REQUEST_TIME - 604800);
  $AxisX->forceMaximum(REQUEST_TIME);
  $AxisX->setLabelInterval(7200);
  $AxisX->setTitle(t('Date (@timezone)', array('@timezone' => yplog_graph_timezone())), array('size' => 7, 'color' => 'gray@0.6'));
  $AxisY =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
  $AxisY->setLabelInterval(ceil($max_listeners/10));    
  $AxisY->setTitle(t('Listeners'), array('size' => 7, 'color' => 'gray@0.6', 'angle' => 90));
  $Graph->setBackgroundColor('green@0.2');
  $Graph->setBorderColor('black');
  $Graph->setPadding(10);
  $Plot->setBorderColor('black');    
  $Graph->done(array('filename' => \Drupal::service("file_system")->realpath($filename)));
}

/**
 * Formats the date for X axis.
 */
function yplog_graph_date($timestamp) {
  $date = date_create('@' . $timestamp);
  // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $date->setTimezone(timezone_open(variable_get('date_default_timezone', 'UTC')));

  return $date->format('n-j');
}

/**
 * Formats the timezone for X axis.
 */
function yplog_graph_timezone() {
  $date = date_create('@' . REQUEST_TIME);
  // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $date->setTimezone(timezone_open(variable_get('date_default_timezone', 'UTC')));

  return $date->format('T O');
}
